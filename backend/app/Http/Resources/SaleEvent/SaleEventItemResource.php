<?php

declare(strict_types=1);

namespace App\Http\Resources\SaleEvent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single sale event item (variant-to-price mapping).
 *
 * Exposes the sale price, optional compare price, quantity caps,
 * and sold count for a specific variant in a sale event.
 *
 * @mixin \App\Domain\SaleEvent\Models\SaleEventItem
 */
class SaleEventItemResource extends JsonResource
{
    /**
     * Transform the sale event item into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'sale_event_id'         => $this->sale_event_id,
            'product_variant_id'    => $this->product_variant_id,
            'sale_price'            => $this->sale_price,
            'compare_price'         => $this->compare_price,
            'max_qty_per_customer'  => $this->max_qty_per_customer,
            'max_qty_total'         => $this->max_qty_total,
            'sold_count'            => $this->sold_count,
            'is_active'             => $this->is_active,
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
