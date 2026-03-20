<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Exceptions\OrderNotCancellableException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Staff\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Action to cancel an order and release reserved inventory.
 *
 * Enforces cancellability rules before applying the status change.
 * Inventory reservations are released on a best-effort basis so
 * a single item failure does not block the cancellation.
 */
class CancelOrderAction
{
    /**
     * Execute the order cancellation.
     *
     * @param  Order   $order   The order to cancel.
     * @param  string  $reason  Human-readable reason for the cancellation.
     * @param  User    $actor   The staff member performing the cancellation.
     * @return Order            The updated order instance.
     *
     * @throws OrderNotCancellableException  If the order is past the cancellable threshold.
     */
    public function execute(Order $order, string $reason, User $actor): Order
    {
        if (!$order->isCancellable()) {
            throw new OrderNotCancellableException($order->order_number, $order->status);
        }

        $fromStatus = $order->status;

        $order->update([
            'status'               => 'cancelled',
            'cancelled_at'         => now(),
            'cancellation_reason'  => $reason,
        ]);

        OrderStatusHistory::create([
            'order_id'            => $order->id,
            'from_status'         => $fromStatus,
            'to_status'           => 'cancelled',
            'note'                => $reason,
            'is_customer_visible' => true,
            'created_by'          => $actor->id,
            'created_at'          => now(),
        ]);

        $this->releaseInventory($order);

        return $order->refresh();
    }

    /**
     * Release inventory reservations for all items in the cancelled order.
     *
     * Failures are logged individually so a single item problem does not
     * prevent the remaining items from being released.
     *
     * @param  Order  $order
     */
    private function releaseInventory(Order $order): void
    {
        if ($order->warehouse_id === null) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            try {
                $inventory = \App\Domain\Inventory\Models\Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->first();

                if ($inventory) {
                    $toRelease = min($item->quantity, $inventory->quantity_reserved);
                    $inventory->decrement('quantity_reserved', $toRelease);
                    $inventory->increment('quantity_available', $toRelease);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to release inventory reservation on order cancellation.', [
                    'order_id'           => $order->id,
                    'order_item_id'      => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'error'              => $e->getMessage(),
                ]);
            }
        }
    }
}
