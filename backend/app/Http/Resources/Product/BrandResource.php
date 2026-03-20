<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single brand.
 *
 * @mixin \App\Domain\Product\Models\Brand
 */
class BrandResource extends JsonResource
{
    /**
     * Transform the brand into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'logo'        => $this->logo,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
