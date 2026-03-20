<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

/**
 * Action: mark a dispatched stock transfer as received, adding inventory to the destination warehouse.
 *
 * Sets the transfer status to 'received', records received_at,
 * adds stock to the to_warehouse via 'transfer_in' transactions,
 * and updates quantity_received on each item.
 * All operations run within a single database transaction with row-level locks.
 */
class ReceiveStockTransferAction
{
    /**
     * Execute the stock transfer receipt.
     *
     * @param  StockTransfer  $stockTransfer  The dispatched transfer being received.
     * @param  string         $receivedBy     UUID of the staff user receiving the transfer.
     *
     * @return StockTransfer  The updated stock transfer.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(StockTransfer $stockTransfer, string $receivedBy): StockTransfer
    {
        return DB::transaction(function () use ($stockTransfer, $receivedBy): StockTransfer {
            $stockTransfer->loadMissing('items');

            foreach ($stockTransfer->items as $item) {
                /** @var StockTransferItem $item */
                $qtyToReceive = $item->quantity_dispatched ?? $item->quantity_requested;

                /** @var Inventory $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $stockTransfer->to_warehouse_id)
                    ->lockForUpdate()
                    ->firstOrCreate(
                        [
                            'product_variant_id' => $item->product_variant_id,
                            'warehouse_id'        => $stockTransfer->to_warehouse_id,
                        ],
                        [
                            'quantity_on_hand'   => 0,
                            'quantity_reserved'  => 0,
                            'quantity_available' => 0,
                            'quantity_incoming'  => 0,
                            'updated_at'         => now(),
                        ]
                    );

                $before = $inventory->quantity_on_hand;

                $inventory->quantity_on_hand   += $qtyToReceive;
                $inventory->quantity_available  = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                $inventory->updated_at          = now();
                $inventory->save();

                InventoryTransaction::create([
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id'       => $stockTransfer->to_warehouse_id,
                    'type'               => 'transfer_in',
                    'quantity'           => $qtyToReceive,
                    'quantity_before'    => $before,
                    'quantity_after'     => $inventory->quantity_on_hand,
                    'reference_type'     => 'stock_transfer',
                    'reference_id'       => $stockTransfer->id,
                    'created_by'         => $receivedBy,
                    'created_at'         => now(),
                ]);

                $item->update(['quantity_received' => $qtyToReceive]);
            }

            $stockTransfer->update([
                'status'      => 'received',
                'received_at' => now(),
            ]);

            return $stockTransfer->fresh(['items', 'fromWarehouse', 'toWarehouse']);
        });
    }
}
