<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderReceipt;
use Illuminate\Support\Facades\DB;

/**
 * Action: record the physical receipt of goods against a purchase order.
 *
 * Performs the following within a single database transaction:
 *  1. Creates a PurchaseOrderReceipt record.
 *  2. Updates each PurchaseOrderItem's quantity_received.
 *  3. Creates a 'receive' InventoryTransaction for each item.
 *  4. Increments quantity_on_hand and quantity_available on the Inventory record;
 *     decrements quantity_incoming.
 *  5. Sets the PurchaseOrder status to 'fully_received' if all items are
 *     fully received, or 'partial_received' otherwise.
 */
class ReceivePurchaseOrderAction
{
    /**
     * Execute the goods-receipt for a purchase order.
     *
     * @param  PurchaseOrder                  $purchaseOrder  The PO being received.
     * @param  array<string,mixed>            $data           Receipt data:
     *   - received_by   (string)  UUID of the staff member receiving goods.
     *   - notes         (string|null)
     *   - attachments   (array)   Document URLs or metadata.
     *   - receipt_items (array)   Each element: { po_item_id, qty_received, condition?, notes? }.
     *
     * @return PurchaseOrderReceipt  The newly created receipt record.
     */
    public function execute(PurchaseOrder $purchaseOrder, array $data): PurchaseOrderReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $data) {
            // 1. Create receipt header
            /** @var PurchaseOrderReceipt $receipt */
            $receipt = PurchaseOrderReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'receipt_number'    => 'REC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'received_by'       => $data['received_by'],
                'received_at'       => now(),
                'notes'             => $data['notes'] ?? null,
                'attachments'       => $data['attachments'] ?? [],
                'receipt_items'     => $data['receipt_items'] ?? [],
            ]);

            // Load PO items for lookup
            $purchaseOrder->loadMissing('items');

            foreach ($data['receipt_items'] as $receiptItem) {
                $poItemId    = $receiptItem['po_item_id'];
                $qtyReceived = (int) $receiptItem['qty_received'];

                if ($qtyReceived <= 0) {
                    continue;
                }

                // 2. Update item's received quantity
                /** @var \App\Domain\Inventory\Models\PurchaseOrderItem|null $poItem */
                $poItem = $purchaseOrder->items->firstWhere('id', $poItemId);

                if (!$poItem) {
                    continue;
                }

                $poItem->increment('quantity_received', $qtyReceived);

                // 3. Create inventory transaction
                /** @var Inventory|null $inventory */
                $inventory = Inventory::where('product_variant_id', $poItem->product_variant_id)
                    ->where('warehouse_id', $purchaseOrder->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $before = $inventory->quantity_on_hand;

                InventoryTransaction::create([
                    'product_variant_id' => $poItem->product_variant_id,
                    'warehouse_id'       => $purchaseOrder->warehouse_id,
                    'type'               => 'receive',
                    'quantity'           => $qtyReceived,
                    'quantity_before'    => $before,
                    'quantity_after'     => $before + $qtyReceived,
                    'unit_cost'          => $poItem->unit_cost,
                    'reference_type'     => 'purchase_order',
                    'reference_id'       => $purchaseOrder->id,
                    'created_by'         => $data['received_by'],
                    'created_at'         => now(),
                ]);

                // 4. Update inventory quantities
                $inventory->quantity_on_hand   += $qtyReceived;
                $inventory->quantity_incoming   = max(0, $inventory->quantity_incoming - $qtyReceived);
                $inventory->quantity_available  = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                $inventory->updated_at          = now();
                $inventory->save();
            }

            // 5. Determine PO completion status
            $purchaseOrder->loadMissing('items');

            $fullyReceived = $purchaseOrder->items->every(
                fn ($item) => $item->quantity_received >= $item->quantity_ordered
            );

            $purchaseOrder->update([
                'status'      => $fullyReceived ? 'fully_received' : 'partial_received',
                'received_at' => now(),
            ]);

            return $receipt;
        });
    }
}
