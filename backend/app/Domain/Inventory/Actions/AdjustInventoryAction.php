<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Action: apply a manual inventory adjustment for a product variant in a warehouse.
 *
 * Accepts a positive or negative quantity delta. Negative adjustments that would
 * reduce on-hand stock below zero are rejected with InsufficientStockException.
 * All changes are wrapped in a database transaction with a row-level lock to
 * prevent concurrent modification.
 */
class AdjustInventoryAction
{
    /**
     * Execute the inventory adjustment.
     *
     * @param  string  $variantId    UUID of the product variant to adjust.
     * @param  string  $warehouseId  UUID of the warehouse holding the stock.
     * @param  int     $quantityDelta Positive to add stock, negative to remove it.
     * @param  string  $reason       Human-readable reason for the adjustment.
     * @param  string  $createdBy    UUID of the staff user initiating the adjustment.
     *
     * @throws InsufficientStockException  If a negative delta would push on-hand below zero.
     * @return InventoryTransaction        The recorded transaction.
     */
    public function execute(
        string $variantId,
        string $warehouseId,
        int    $quantityDelta,
        string $reason,
        string $createdBy,
    ): InventoryTransaction {
        return DB::transaction(function () use ($variantId, $warehouseId, $quantityDelta, $reason, $createdBy) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $inventory->quantity_on_hand;
            $after  = $before + $quantityDelta;

            if ($after < 0) {
                throw new InsufficientStockException($variantId, abs($quantityDelta), $before);
            }

            $inventory->quantity_on_hand   = $after;
            $inventory->quantity_available = $after - $inventory->quantity_reserved;
            $inventory->updated_at         = now();
            $inventory->save();

            /** @var InventoryTransaction $transaction */
            $transaction = InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'adjustment',
                'quantity'           => $quantityDelta,
                'quantity_before'    => $before,
                'quantity_after'     => $after,
                'notes'              => $reason,
                'created_by'         => $createdBy,
                'created_at'         => now(),
            ]);

            return $transaction;
        });
    }
}
