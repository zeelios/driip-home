<?php

declare(strict_types=1);

namespace App\Http\Resources\SaleEvent;

use App\Http\Resources\Product\ProductVariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single sale event.
 *
 * Items are included when the 'items' relationship is eager-loaded.
 * Each item embeds its variant using ProductVariantResource when the
 * 'variant' relationship is also loaded on the item.
 *
 * @mixin \App\Domain\SaleEvent\Models\SaleEvent
 */
class SaleEventResource extends JsonResource
{
    /**
     * Transform the sale event into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'type'             => $this->type,
            'status'           => $this->status,
            'is_active'        => $this->isActive(),
            'is_scheduled'     => $this->isScheduled(),
            'starts_at'        => $this->starts_at?->toIso8601String(),
            'ends_at'          => $this->ends_at?->toIso8601String(),
            'max_orders_total' => $this->max_orders_total,
            'is_public'        => $this->is_public,
            'banner_url'       => $this->banner_url,
            'created_by'       => $this->created_by,
            'items'            => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->map(fn ($item) => [
                    'id'                  => $item->id,
                    'product_variant_id'  => $item->product_variant_id,
                    'sale_price'          => $item->sale_price,
                    'compare_price'       => $item->compare_price,
                    'max_qty_per_customer' => $item->max_qty_per_customer,
                    'max_qty_total'       => $item->max_qty_total,
                    'sold_count'          => $item->sold_count,
                    'is_active'           => $item->is_active,
                    'variant'             => $item->relationLoaded('variant') && $item->variant !== null
                        ? new ProductVariantResource($item->variant)
                        : null,
                ]),
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
