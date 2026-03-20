<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Manages stock reservations for orders.
 *
 * When an order is created, stock is reserved (allocated) but not yet deducted.
 * When the order ships, the reservation is converted to a 'ship' transaction.
 * When the order is cancelled, the reservation is released.
 *
 * Uses PostgreSQL row-level locking (lockForUpdate) to prevent race conditions
 * when multiple orders are placed concurrently for the same variant.
 */
class StockReservationService
{
    /**
     * Reserve stock for an order item.
     *
     * @param  string  $variantId
     * @param  string  $warehouseId
     * @param  int     $quantity
     * @param  string  $orderId
     *
     * @throws InsufficientStockException  If available stock is less than requested.
     */
    public function reserve(string $variantId, string $warehouseId, int $quantity, string $orderId): void
    {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $orderId) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventory->quantity_available < $quantity) {
                throw new InsufficientStockException($variantId, $quantity, $inventory->quantity_available);
            }

            $before = $inventory->quantity_reserved;
            $inventory->increment('quantity_reserved', $quantity);
            $inventory->decrement('quantity_available', $quantity);
            $inventory->touch('updated_at');

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'reserve',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => $before + $quantity,
                'reference_type'     => 'order',
                'reference_id'       => $orderId,
            ]);
        });
    }

    /**
     * Release a previously made stock reservation (e.g. order cancelled).
     *
     * @param  string  $variantId
     * @param  string  $warehouseId
     * @param  int     $quantity
     * @param  string  $orderId
     */
    public function release(string $variantId, string $warehouseId, int $quantity, string $orderId): void
    {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $orderId) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) return;

            $before = $inventory->quantity_reserved;
            $inventory->decrement('quantity_reserved', min($quantity, $before));
            $inventory->increment('quantity_available', min($quantity, $before));
            $inventory->touch('updated_at');

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'release',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => max(0, $before - $quantity),
                'reference_type'     => 'order',
                'reference_id'       => $orderId,
            ]);
        });
    }
}
