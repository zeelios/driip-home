<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Data\RecordPaymentDto;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderPayment;
use App\Domain\Order\Services\OrderActivityLogger;

/**
 * Action to record a payment event for an order.
 *
 * Creates an OrderPayment record for history tracking and updates the
 * order's deposit amount and payment status accordingly.
 */
class RecordPaymentAction
{
    public function __construct(
        private readonly OrderActivityLogger $activityLogger
    ) {
    }

    /**
     * Execute the payment recording.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return OrderPayment
     */
    public function execute(Order $order, RecordPaymentDto $dto): OrderPayment
    {
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $dto->amount,
            'payment_method' => $dto->paymentMethod,
            'payment_type' => $dto->paymentType,
            'reference' => $dto->reference,
            'proof_urls' => $dto->proofUrls,
            'notes' => $dto->notes,
            'recorded_by' => $dto->recordedBy,
        ]);

        $this->updateOrderPaymentState($order, $dto);

        $this->activityLogger->logPaymentRecorded(
            $order,
            $dto->amount,
            $dto->paymentMethod,
            $dto->paymentType,
            $dto->recordedBy
        );

        return $payment;
    }

    /**
     * Update the order's payment state based on the payment type.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function updateOrderPaymentState(Order $order, RecordPaymentDto $dto): void
    {
        match ($dto->paymentType) {
            'deposit' => $this->applyDeposit($order, $dto),
            'final' => $this->applyFinalPayment($order, $dto),
            'cod_collection' => $this->applyCodCollection($order, $dto),
            'refund' => $this->applyRefund($order, $dto),
            'adjustment' => $this->applyAdjustment($order, $dto),
            default => null,
        };
    }

    /**
     * Apply a deposit payment to the order.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function applyDeposit(Order $order, RecordPaymentDto $dto): void
    {
        $newDeposit = $order->deposit_amount + $dto->amount;
        $isFullyPaid = $newDeposit >= $order->total_after_tax;

        $order->update([
            'deposit_amount' => $newDeposit,
            'deposit_paid_at' => now(),
            'payment_status' => $isFullyPaid ? 'paid' : 'partial',
        ]);
    }

    /**
     * Apply a final payment to the order.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function applyFinalPayment(Order $order, RecordPaymentDto $dto): void
    {
        $order->update([
            'deposit_amount' => $order->total_after_tax,
            'payment_status' => 'paid',
            'payment_method' => $dto->paymentMethod,
            'payment_reference' => $dto->reference,
            'paid_at' => now(),
        ]);
    }

    /**
     * Apply a COD collection to the order.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function applyCodCollection(Order $order, RecordPaymentDto $dto): void
    {
        $order->update([
            'cod_collected_amount' => $dto->amount,
            'cod_collected_at' => now(),
            'cod_collection_reference' => $dto->reference,
        ]);

        $this->reconcileCodCollection($order);
    }

    /**
     * Apply a refund to the order.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function applyRefund(Order $order, RecordPaymentDto $dto): void
    {
        $newDeposit = max(0, $order->deposit_amount - $dto->amount);

        $order->update([
            'deposit_amount' => $newDeposit,
            'payment_status' => $newDeposit >= $order->total_after_tax ? 'paid' : ($newDeposit > 0 ? 'partial' : 'unpaid'),
        ]);
    }

    /**
     * Apply a payment adjustment to the order.
     *
     * @param  Order               $order
     * @param  RecordPaymentDto  $dto
     * @return void
     */
    private function applyAdjustment(Order $order, RecordPaymentDto $dto): void
    {
        // Adjustments modify the deposit amount directly
        $newDeposit = max(0, $order->deposit_amount + $dto->amount);

        $order->update([
            'deposit_amount' => $newDeposit,
            'payment_status' => $newDeposit >= $order->total_after_tax ? 'paid' : ($newDeposit > 0 ? 'partial' : 'unpaid'),
        ]);
    }

    /**
     * Reconcile COD collection against expected amount.
     *
     * @param  Order  $order
     * @return void
     */
    private function reconcileCodCollection(Order $order): void
    {
        if ($order->cod_expected_amount === null) {
            return;
        }

        $discrepancy = ($order->cod_collected_amount ?? 0) - $order->cod_expected_amount;

        if ($discrepancy === 0) {
            $order->update(['cod_reconciliation_status' => 'matched']);
        } elseif ($discrepancy !== 0) {
            $order->update([
                'cod_reconciliation_status' => 'disputed',
                'cod_discrepancy_amount' => $discrepancy,
            ]);
        }
    }
}
