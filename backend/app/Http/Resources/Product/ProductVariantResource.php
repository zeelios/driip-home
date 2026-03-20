<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single product variant.
 *
 * Exposes pricing fields including the computed effective_price which
 * already accounts for any active sale override. Stock information
 * is included only when the 'inventory' relationship is eager-loaded.
 *
 * @mixin \App\Domain\Product\Models\ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * Transform the product variant into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'sku'              => $this->sku,
            'barcode'          => $this->barcode,
            'attribute_values' => $this->attribute_values,
            'compare_price'    => $this->compare_price,
            'cost_price'       => $this->cost_price,
            'selling_price'    => $this->selling_price,
            'sale_price'       => $this->sale_price,
            'effective_price'  => $this->effectivePrice(),
            'weight_grams'     => $this->weight_grams,
            'status'           => $this->status,
            'sort_order'       => $this->sort_order,
            'stock'            => $this->when(
                $this->relationLoaded('inventory'),
                fn () => [
                    'total'   => $this->inventory->sum('quantity_on_hand'),
                    'details' => $this->inventory->map(fn ($inv) => [
                        'warehouse_id'      => $inv->warehouse_id,
                        'quantity_on_hand'  => $inv->quantity_on_hand,
                        'quantity_reserved' => $inv->quantity_reserved,
                    ]),
                ],
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
