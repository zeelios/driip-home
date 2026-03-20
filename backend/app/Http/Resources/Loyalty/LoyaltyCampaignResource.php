<?php

declare(strict_types=1);

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a LoyaltyCampaign model into a JSON-serializable array.
 *
 * @mixin \App\Domain\Loyalty\Models\LoyaltyCampaign
 */
class LoyaltyCampaignResource extends JsonResource
{
    /**
     * Transform the loyalty campaign into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'type'         => $this->type,
            'multiplier'   => $this->multiplier,
            'bonus_points' => $this->bonus_points,
            'conditions'   => $this->conditions ?? [],
            'starts_at'    => $this->starts_at?->toIso8601String(),
            'ends_at'      => $this->ends_at?->toIso8601String(),
            'is_active'    => $this->is_active,
            'created_by'   => $this->created_by,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
