<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\StockCount;
use App\Domain\Inventory\Models\StockCountItem;
use Illuminate\Support\Facades\DB;

/**
 * Action: approve a completed stock count and apply inventory corrections.
 *
 * Transitions the stock count to 'approved', then for every item with a non-zero
 * variance, creates a 'count_correction' InventoryTransaction and adjusts the
 * inventory quantities accordingly.
 * All corrections are applied within a single database transaction.
 */
class ApproveStockCountAction
{
    /**
     * Execute the stock count approval and apply count corrections to inventory.
     *
     * @param  StockCount  $stockCount  The completed stock count to approve.
     * @param  string      $approvedBy  UUID of the staff user approving the count.
     *
     * @return StockCount  The updated stock count.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(StockCount $stockCount, string $approvedBy): StockCount
    {
        return DB::transaction(function () use ($stockCount, $approvedBy): StockCount {
            $stockCount->loadMissing('items');

            foreach ($stockCount->items as $item) {
                /** @var StockCountItem $item */
                if ($item->variance === null || $item->variance === 0) {
                    continue;
                }

                /** @var Inventory|null $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $stockCount->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $before = $inventory->quantity_on_hand;
                $after  = $item->quantity_counted ?? $before;

                $inventory->quantity_on_hand   = $after;
                $inventory->quantity_available  = $after - $inventory->quantity_reserved;
                $inventory->last_counted_at    = now();
                $inventory->updated_at          = now();
                $inventory->save();

                InventoryTransaction::create([
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id'       => $stockCount->warehouse_id,
                    'type'               => 'count_correction',
                    'quantity'           => $item->variance,
                    'quantity_before'    => $before,
                    'quantity_after'     => $after,
                    'reference_type'     => 'stock_count',
                    'reference_id'       => $stockCount->id,
                    'notes'              => "Stock count correction: count #{$stockCount->count_number}",
                    'created_by'         => $approvedBy,
                    'created_at'         => now(),
                ]);
            }

            $stockCount->update([
                'status'      => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $stockCount->fresh(['items', 'warehouse']);
        });
    }
}
