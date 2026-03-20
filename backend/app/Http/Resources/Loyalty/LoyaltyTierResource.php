<?php

declare(strict_types=1);

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a LoyaltyTier model into a JSON-serializable array.
 *
 * @mixin \App\Domain\Loyalty\Models\LoyaltyTier
 */
class LoyaltyTierResource extends JsonResource
{
    /**
     * Transform the loyalty tier into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'min_lifetime_points' => $this->min_lifetime_points,
            'discount_percent'    => $this->discount_percent,
            'free_shipping'       => $this->free_shipping,
            'early_access'        => $this->early_access,
            'birthday_multiplier' => $this->birthday_multiplier,
            'perks'               => $this->perks ?? [],
            'color'               => $this->color,
            'sort_order'          => $this->sort_order,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
