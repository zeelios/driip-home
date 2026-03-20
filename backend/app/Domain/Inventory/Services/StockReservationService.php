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
 * When the order ships, the reservation is converted to a 'ship' transaction
 * via the fulfill() method. When the order is cancelled, the reservation is
 * released via the release() method.
 *
 * Uses PostgreSQL row-level locking (lockForUpdate) to prevent race conditions
 * when multiple orders are placed concurrently for the same variant.
 */
class StockReservationService
{
    /**
     * Reserve stock for a reference entity (e.g. an order item).
     *
     * Decrements quantity_available and increments quantity_reserved.
     * Throws InsufficientStockException if available stock is less than requested.
     *
     * @param  string  $variantId      UUID of the product variant to reserve.
     * @param  string  $warehouseId    UUID of the warehouse holding the stock.
     * @param  int     $quantity       Number of units to reserve.
     * @param  string  $referenceType  Type of the referencing entity (e.g. 'order').
     * @param  string  $referenceId    UUID of the referencing entity.
     *
     * @throws InsufficientStockException  If available stock is less than requested.
     *
     * @return void
     */
    public function reserve(
        string $variantId,
        string $warehouseId,
        int    $quantity,
        string $referenceType,
        string $referenceId,
    ): void {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $referenceType, $referenceId) {
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
            $inventory->updated_at = now();
            $inventory->save();

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'reserve',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => $before + $quantity,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'created_at'         => now(),
            ]);
        });
    }

    /**
     * Release a previously made stock reservation (e.g. order cancelled).
     *
     * Increments quantity_available and decrements quantity_reserved.
     * Silently no-ops if the inventory record does not exist.
     *
     * @param  string  $variantId      UUID of the product variant.
     * @param  string  $warehouseId    UUID of the warehouse.
     * @param  int     $quantity       Number of units to release.
     * @param  string  $referenceType  Type of the referencing entity (e.g. 'order').
     * @param  string  $referenceId    UUID of the referencing entity.
     *
     * @return void
     */
    public function release(
        string $variantId,
        string $warehouseId,
        int    $quantity,
        string $referenceType,
        string $referenceId,
    ): void {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $referenceType, $referenceId) {
            /** @var Inventory|null $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                return;
            }

            $before       = $inventory->quantity_reserved;
            $actualRelease = min($quantity, $before);

            $inventory->decrement('quantity_reserved', $actualRelease);
            $inventory->increment('quantity_available', $actualRelease);
            $inventory->updated_at = now();
            $inventory->save();

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'release',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => max(0, $before - $quantity),
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'created_at'         => now(),
            ]);
        });
    }

    /**
     * Fulfill a reservation by converting it into an actual stock deduction ('ship').
     *
     * Decrements both quantity_on_hand and quantity_reserved, as the goods
     * are now physically leaving the warehouse. Does NOT change quantity_available
     * since that was already decremented when the reservation was created.
     *
     * @param  string  $variantId      UUID of the product variant.
     * @param  string  $warehouseId    UUID of the warehouse.
     * @param  int     $quantity       Number of units being shipped.
     * @param  string  $referenceType  Type of the referencing entity (e.g. 'order').
     * @param  string  $referenceId    UUID of the referencing entity.
     *
     * @return void
     */
    public function fulfill(
        string $variantId,
        string $warehouseId,
        int    $quantity,
        string $referenceType,
        string $referenceId,
    ): void {
        DB::transaction(function () use ($variantId, $warehouseId, $quantity, $referenceType, $referenceId) {
            /** @var Inventory|null $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                return;
            }

            $before = $inventory->quantity_on_hand;

            $inventory->decrement('quantity_on_hand', $quantity);
            $inventory->decrement('quantity_reserved', min($quantity, $inventory->quantity_reserved));
            $inventory->updated_at = now();
            $inventory->save();

            InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'ship',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => $inventory->quantity_on_hand,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'created_at'         => now(),
            ]);
        });
    }
}
