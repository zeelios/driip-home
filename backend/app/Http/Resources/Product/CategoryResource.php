<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single category.
 *
 * Child categories are included when the 'children' relationship
 * has been eager-loaded by the calling controller.
 *
 * @mixin \App\Domain\Product\Models\Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the category into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'parent_id'   => $this->parent_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image'       => $this->image,
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
