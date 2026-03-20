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
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'sku_base'          => $this->sku_base,
            'brand'             => $this->when(
                $this->relationLoaded('brand') && $this->brand !== null,
                fn () => [
                    'id'   => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                ],
            ),
            'category'          => $this->when(
                $this->relationLoaded('category') && $this->category !== null,
                fn () => [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ],
            ),
            'gender'            => $this->gender,
            'season'            => $this->season,
            'tags'              => $this->tags,
            'status'            => $this->status,
            'is_featured'       => $this->is_featured,
            'published_at'      => $this->published_at?->toIso8601String(),
            'short_description' => $this->short_description,
            'description'       => $this->description,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'variants'          => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
