<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

/**
 * Action: dispatch a stock transfer, deducting inventory from the source warehouse.
 *
 * Sets the transfer status to 'dispatched', records dispatched_at,
 * deducts stock from the from_warehouse via 'transfer_out' transactions,
 * and updates quantity_dispatched on each item.
 * All operations run within a single database transaction with row-level locks.
 */
class DispatchStockTransferAction
{
    /**
     * Execute the stock transfer dispatch.
     *
     * @param  StockTransfer  $stockTransfer  The approved transfer to dispatch.
     * @param  string         $dispatchedBy   UUID of the staff user dispatching the transfer.
     *
     * @return StockTransfer  The updated stock transfer.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(StockTransfer $stockTransfer, string $dispatchedBy): StockTransfer
    {
        return DB::transaction(function () use ($stockTransfer, $dispatchedBy): StockTransfer {
            $stockTransfer->loadMissing('items');

            foreach ($stockTransfer->items as $item) {
                /** @var StockTransferItem $item */
                $qtyToDispatch = $item->quantity_requested;

                /** @var Inventory|null $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $stockTransfer->from_warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $before = $inventory->quantity_on_hand;

                $inventory->quantity_on_hand   -= $qtyToDispatch;
                $inventory->quantity_available  = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                $inventory->updated_at          = now();
                $inventory->save();

                InventoryTransaction::create([
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id'       => $stockTransfer->from_warehouse_id,
                    'type'               => 'transfer_out',
                    'quantity'           => $qtyToDispatch,
                    'quantity_before'    => $before,
                    'quantity_after'     => $inventory->quantity_on_hand,
                    'reference_type'     => 'stock_transfer',
                    'reference_id'       => $stockTransfer->id,
                    'created_by'         => $dispatchedBy,
                    'created_at'         => now(),
                ]);

                $item->update(['quantity_dispatched' => $qtyToDispatch]);
            }

            $stockTransfer->update([
                'status'       => 'dispatched',
                'dispatched_at' => now(),
            ]);

            return $stockTransfer->fresh(['items', 'fromWarehouse', 'toWarehouse']);
        });
    }
}
