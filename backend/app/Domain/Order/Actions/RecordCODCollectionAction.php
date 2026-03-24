<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Services\OrderActivityLogger;

/**
 * Action to record COD (Cash on Delivery) collection.
 *
 * Records the amount collected by courier, reconciles against expected,
 * and updates the order's COD tracking fields.
 */
class RecordCODCollectionAction
{
    public function __construct(
        private readonly RecordPaymentAction $recordPayment,
        private readonly OrderActivityLogger $activityLogger
    ) {
    }

    /**
     * Execute the COD collection recording.
     *
     * @param  Order       $order
     * @param  int         $collectedAmount
     * @param  string|null $reference
     * @param  string|null $notes
     * @param  string|null $recordedBy
     * @return array{payment: \App\Domain\Order\Models\OrderPayment, discrepancy: int, status: string}
     *
     * @throws \InvalidArgumentException If order is not a COD order
     */
    public function execute(
        Order $order,
        int $collectedAmount,
        ?string $reference = null,
        ?string $notes = null,
        ?string $recordedBy = null
    ): array {
        if ($order->payment_method !== 'cod') {
            throw new \InvalidArgumentException('Order is not a COD order');
        }

        $expectedAmount = $order->cod_expected_amount ?? $order->total_after_tax;
        $discrepancy = $collectedAmount - $expectedAmount;

        // Determine reconciliation status
        $reconciliationStatus = match (true) {
            $discrepancy === 0 => 'matched',
            $discrepancy !== 0 => 'disputed',
            default => 'pending',
        };

        // Record the payment
        $payment = $this->recordPayment->execute($order, new \App\Domain\Order\Data\RecordPaymentDto(
            amount: $collectedAmount,
            paymentMethod: 'cod',
            paymentType: 'cod_collection',
            reference: $reference,
            notes: $notes,
            recordedBy: $recordedBy,
        ));

        // Update order COD fields
        $order->update([
            'cod_collected_amount' => $collectedAmount,
            'cod_collected_at' => now(),
            'cod_collection_reference' => $reference,
            'cod_reconciliation_status' => $reconciliationStatus,
            'cod_discrepancy_amount' => $discrepancy !== 0 ? $discrepancy : null,
        ]);

        // Mark order as paid if fully collected
        if ($collectedAmount >= $expectedAmount) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Log the collection
        $this->activityLogger->log(
            $order,
            'cod_collected',
            "COD collected: {$collectedAmount} (expected: {$expectedAmount})",
            [
                'collected_amount' => $collectedAmount,
                'expected_amount' => $expectedAmount,
                'discrepancy' => $discrepancy,
                'reference' => $reference,
                'status' => $reconciliationStatus,
            ],
            null,
            $recordedBy ? 'staff' : 'courier'
        );

        return [
            'payment' => $payment,
            'discrepancy' => $discrepancy,
            'status' => $reconciliationStatus,
        ];
    }

    /**
     * Mark a COD discrepancy as waived (resolved without action).
     *
     * @param  Order       $order
     * @param  string|null $reason
     * @param  string|null $waivedBy
     * @return void
     */
    public function waiveDiscrepancy(Order $order, ?string $reason = null, ?string $waivedBy = null): void
    {
        if ($order->cod_reconciliation_status !== 'disputed') {
            throw new \InvalidArgumentException('Order does not have a disputed COD status');
        }

        $order->update([
            'cod_reconciliation_status' => 'waived',
        ]);

        $this->activityLogger->log(
            $order,
            'cod_discrepancy_waived',
            "COD discrepancy waived: {$reason}",
            [
                'discrepancy_amount' => $order->cod_discrepancy_amount,
                'reason' => $reason,
                'waived_by' => $waivedBy,
            ],
            null,
            $waivedBy ? 'staff' : 'system'
        );
    }
}
