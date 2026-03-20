<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Data\CreatePurchaseOrderDto;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;

/**
 * Action: create a new purchase order with its line items.
 *
 * Generates a sequential PO number (DRP-PO-00001), creates the PurchaseOrder
 * header, and inserts each PurchaseOrderItem within a single transaction.
 * Total cost is calculated from sum of (unit_cost × quantity_ordered) plus
 * any shipping/other costs.
 */
class CreatePurchaseOrderAction
{
    use GeneratesCode;

    /**
     * Execute the purchase order creation.
     *
     * @param  CreatePurchaseOrderDto  $dto  Validated data for the new purchase order.
     *
     * @return PurchaseOrder  The newly created purchase order with items loaded.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(CreatePurchaseOrderDto $dto): PurchaseOrder
    {
        return DB::transaction(function () use ($dto): PurchaseOrder {
            $sequence = PurchaseOrder::withTrashed()->count() + 1;

            $itemsTotal = array_sum(array_map(
                fn ($item) => $item['unit_cost'] * $item['quantity_ordered'],
                $dto->items
            ));

            $totalCost = $itemsTotal + ($dto->shippingCost ?? 0) + ($dto->otherCosts ?? 0);

            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::create([
                'po_number'           => $this->buildCode('DRP-PO', $sequence, 5),
                'supplier_id'         => $dto->supplierId,
                'warehouse_id'        => $dto->warehouseId,
                'status'              => 'draft',
                'expected_arrival_at' => $dto->expectedArrivalAt,
                'shipping_cost'       => $dto->shippingCost ?? 0,
                'other_costs'         => $dto->otherCosts ?? 0,
                'total_cost'          => $totalCost,
                'notes'               => $dto->notes,
                'created_by'          => $dto->createdBy,
            ]);

            foreach ($dto->items as $itemData) {
                /** @var ProductVariant|null $variant */
                $variant = ProductVariant::find($itemData['product_variant_id']);

                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'sku'                => $variant?->sku ?? $itemData['sku'] ?? '',
                    'quantity_ordered'   => $itemData['quantity_ordered'],
                    'quantity_received'  => 0,
                    'unit_cost'          => $itemData['unit_cost'],
                    'total_cost'         => $itemData['unit_cost'] * $itemData['quantity_ordered'],
                    'notes'              => $itemData['notes'] ?? null,
                ]);
            }

            return $po->load(['items', 'supplier', 'warehouse']);
        });
    }
}
