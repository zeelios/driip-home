<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single product.
 *
 * Brand and category are inlined as compact objects (id, name, slug) rather
 * than nested full resources to keep list responses lightweight. Variants are
 * included as a full VariantResource collection only when eager-loaded.
 *
 * @mixin \App\Domain\Product\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the product into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $pricing = [
            'compare_price' => $this->compare_price,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'sale_price' => $this->sale_price,
            'effective_price' => $this->effectivePrice(),
            'currency' => 'VND',
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku_base' => $this->sku_base,
            'pricing' => $pricing,
            'compare_price' => $pricing['compare_price'],
            'cost_price' => $pricing['cost_price'],
            'selling_price' => $pricing['selling_price'],
            'sale_price' => $pricing['sale_price'],
            'effective_price' => $pricing['effective_price'],
            'brand' => $this->when(
                $this->relationLoaded('brand') && $this->brand !== null,
                fn () => [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                ],
            ),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category !== null,
                fn () => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ],
            ),
            'gender' => $this->gender,
            'season' => $this->season,
            'tags' => $this->tags,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
