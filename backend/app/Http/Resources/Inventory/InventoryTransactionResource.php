<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single inventory transaction.
 *
 * @mixin \App\Domain\Inventory\Models\InventoryTransaction
 */
class InventoryTransactionResource extends JsonResource
{
    /**
     * Transform the inventory transaction into an array.
     *
     * Includes inline summaries of the related variant (id, sku) and
     * warehouse (id, code, name) when those relations are loaded.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'type' => $this->type,

            'variant' => $this->whenLoaded('variant', fn () => $this->variant ? [
                'id'  => $this->variant->id,
                'sku' => $this->variant->sku,
            ] : null),

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),

            'quantity'        => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after'  => $this->quantity_after,
            'unit_cost'       => $this->unit_cost,
            'lot_number'      => $this->lot_number,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'notes'           => $this->notes,
            'created_by'      => $this->created_by,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
