<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for ProductPriceHistory.
 *
 * @mixin \App\Domain\Product\Models\ProductPriceHistory
 */
class ProductPriceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'compare_price' => $this->compare_price,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'changed_by' => $this->changed_by,
            'reason' => $this->reason,
            'changed_at' => $this->changed_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
