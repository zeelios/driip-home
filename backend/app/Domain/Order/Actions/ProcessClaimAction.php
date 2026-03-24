<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderClaim;
use App\Domain\Order\Models\OrderReturn;
use App\Domain\Order\Services\OrderActivityLogger;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;

/**
 * Action to process order claims.
 *
 * Handles claim resolution workflows including refunds,
 * replacements, and return initiation.
 */
class ProcessClaimAction
{
    use GeneratesCode;

    public function __construct(
        private readonly OrderActivityLogger $activityLogger
    ) {}

    /**
     * Initiate a return for a claim.
     *
     * @param  OrderClaim  $claim
     * @param  array       $returnItems
     * @param  string|null $notes
     * @return OrderReturn
     */
    public function initiateReturn(OrderClaim $claim, array $returnItems, ?string $notes = null): OrderReturn
    {
        return DB::transaction(function () use ($claim, $returnItems, $notes): OrderReturn {
            $sequence = OrderReturn::count() + 1;

            $return = OrderReturn::create([
                'return_number' => $this->buildCode('DRP-RET', $sequence, 5),
                'order_id'      => $claim->order_id,
                'claim_id'      => $claim->id,
                'status'        => 'requested',
                'return_items'  => $returnItems,
                'notes'         => $notes,
            ]);

            $claim->update(['status' => 'awaiting_return']);

            $this->activityLogger->log(
                $claim->order,
                'return_initiated',
                "Return {$return->return_number} initiated for claim {$claim->claim_number}",
                [
                    'claim_id'      => $claim->id,
                    'return_id'     => $return->id,
                    'return_number' => $return->return_number,
                ]
            );

            return $return;
        });
    }

    /**
     * Issue a replacement for a claim.
     *
     * @param  OrderClaim  $claim
     * @param  array       $replacementItems
     * @return Order
     */
    public function issueReplacement(OrderClaim $claim, array $replacementItems): Order
    {
        return DB::transaction(function () use ($claim, $replacementItems): Order {
            $originalOrder = $claim->order;

            // Create replacement order
            $sequence = Order::withTrashed()->count() + 1;
            $orderNumber = $this->buildOrderNumber($sequence);

            $replacementOrder = Order::create([
                'order_number'       => $orderNumber . '-R',
                'customer_id'        => $originalOrder->customer_id,
                'guest_name'         => $originalOrder->guest_name,
                'guest_email'        => $originalOrder->guest_email,
                'guest_phone'        => $originalOrder->guest_phone,
                'status'             => 'pending',
                'payment_status'     => 'unpaid', // Replacement orders are free
                'subtotal'           => 0,
                'total_before_tax'   => 0,
                'total_after_tax'    => 0,
                'shipping_name'      => $originalOrder->shipping_name,
                'shipping_phone'     => $originalOrder->shipping_phone,
                'shipping_province'  => $originalOrder->shipping_province,
                'shipping_district'  => $originalOrder->shipping_district,
                'shipping_ward'      => $originalOrder->shipping_ward,
                'shipping_address'   => $originalOrder->shipping_address,
                'shipping_zip'       => $originalOrder->shipping_zip,
                'notes'              => 'Replacement for claim ' . $claim->claim_number,
                'source'             => 'replacement',
                'tags'               => ['replacement', 'claim-' . $claim->claim_number],
                'public_token'       => $this->generatePublicToken(),
                'token_expires_at'   => now()->addDays(30),
            ]);

            // Create order items
            foreach ($replacementItems as $item) {
                \App\Domain\Order\Models\OrderItem::create([
                    'order_id'           => $replacementOrder->id,
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'sku'                => $item['sku'],
                    'name'               => $item['name'],
                    'size'               => $item['size'] ?? null,
                    'color'              => $item['color'] ?? null,
                    'unit_price'         => 0,
                    'cost_price'         => $item['cost_price'] ?? 0,
                    'quantity'           => $item['quantity'],
                    'quantity_returned'  => 0,
                    'discount_amount'    => 0,
                    'total_price'        => 0,
                ]);
            }

            $claim->update([
                'status'    => 'resolved',
                'resolution'=> 'replacement',
                'resolved_at'=> now(),
            ]);

            $this->activityLogger->logClaimResolved(
                $originalOrder,
                $claim,
                'replacement',
                null,
                null
            );

            return $replacementOrder;
        });
    }

    /**
     * Process a refund for a claim.
     *
     * @param  OrderClaim  $claim
     * @param  int         $amount
     * @param  string      $method
     * @param  string|null $reference
     * @return OrderClaim
     */
    public function processRefund(OrderClaim $claim, int $amount, string $method, ?string $reference = null): OrderClaim
    {
        return DB::transaction(function () use ($claim, $amount, $method, $reference): OrderClaim {
            $claim->update([
                'status'        => 'resolved',
                'resolution'    => 'refund',
                'refund_amount' => $amount,
                'resolved_at'   => now(),
            ]);

            $this->activityLogger->logRefundProcessed(
                $claim->order,
                $amount,
                $method,
                $reference ?? '',
                null
            );

            $this->activityLogger->logClaimResolved(
                $claim->order,
                $claim,
                'refund',
                $amount,
                null
            );

            return $claim->refresh();
        });
    }

    /**
     * Reject a claim.
     *
     * @param  OrderClaim  $claim
     * @param  string      $reason
     * @return OrderClaim
     */
    public function rejectClaim(OrderClaim $claim, string $reason): OrderClaim
    {
        $claim->update([
            'status'           => 'rejected',
            'resolution'       => 'rejected',
            'resolution_notes' => $reason,
            'resolved_at'      => now(),
        ]);

        $this->activityLogger->logClaimResolved(
            $claim->order,
            $claim,
            'rejected',
            null,
            null
        );

        return $claim->refresh();
    }
}
