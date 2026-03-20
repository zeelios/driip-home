<?php

declare(strict_types=1);

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a LoyaltyAccount model into a JSON-serializable array.
 *
 * Includes an inline tier sub-object via LoyaltyTierResource when the
 * tier relation has been eagerly loaded.
 *
 * @mixin \App\Domain\Loyalty\Models\LoyaltyAccount
 */
class LoyaltyAccountResource extends JsonResource
{
    /**
     * Transform the loyalty account into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'customer_id'       => $this->customer_id,
            'points_balance'    => $this->points_balance,
            'lifetime_points'   => $this->lifetime_points,
            'lifetime_spending' => $this->lifetime_spending,
            'tier'              => $this->whenLoaded(
                'tier',
                fn () => $this->tier !== null ? new LoyaltyTierResource($this->tier) : null,
            ),
            'tier_achieved_at'  => $this->tier_achieved_at?->toIso8601String(),
            'tier_expires_at'   => $this->tier_expires_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
