<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single purchase order.
 *
 * @mixin \App\Domain\Inventory\Models\PurchaseOrder
 */
class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the purchase order into an array.
     *
     * Includes full supplier and warehouse objects when loaded,
     * and the items collection when loaded.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'po_number'           => $this->po_number,
            'status'              => $this->status,
            'expected_arrival_at' => $this->expected_arrival_at?->toDateString(),
            'received_at'         => $this->received_at?->toIso8601String(),
            'shipping_cost'       => $this->shipping_cost,
            'other_costs'         => $this->other_costs,
            'total_cost'          => $this->total_cost,
            'notes'               => $this->notes,
            'created_by'          => $this->created_by,
            'approved_by'         => $this->approved_by,
            'approved_at'         => $this->approved_at?->toIso8601String(),

            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? [
                'id'   => $this->supplier->id,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
            ] : null),

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'sku'               => $item->sku,
                'quantity_ordered'  => $item->quantity_ordered,
                'quantity_received' => $item->quantity_received,
                'unit_cost'         => $item->unit_cost,
                'total_cost'        => $item->total_cost,
                'notes'             => $item->notes,
            ])),

            'receipts' => $this->whenLoaded('receipts', fn () => $this->receipts->map(fn ($receipt) => [
                'id'             => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'received_by'    => $receipt->received_by,
                'received_at'    => $receipt->received_at?->toIso8601String(),
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
