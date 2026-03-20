<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single inventory record.
 *
 * @mixin \App\Domain\Inventory\Models\Inventory
 */
class InventoryResource extends JsonResource
{
    /**
     * Transform the inventory record into an array.
     *
     * Includes an inline product_variant summary (id, sku, product name) and
     * an inline warehouse summary (id, code, name) when the relations are loaded.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product_variant' => $this->whenLoaded('variant', fn () => $this->variant ? [
                'id'   => $this->variant->id,
                'sku'  => $this->variant->sku,
                'name' => $this->variant->product?->name,
            ] : null),

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),

            'quantity_on_hand'   => $this->quantity_on_hand,
            'quantity_reserved'  => $this->quantity_reserved,
            'quantity_available' => $this->quantity_available,
            'quantity_incoming'  => $this->quantity_incoming,
            'reorder_point'      => $this->reorder_point,
            'reorder_quantity'   => $this->reorder_quantity,
            'last_counted_at'    => $this->last_counted_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
