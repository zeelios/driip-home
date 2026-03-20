<?php

declare(strict_types=1);

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a LoyaltyTransaction model into a JSON-serializable array.
 *
 * @mixin \App\Domain\Loyalty\Models\LoyaltyTransaction
 */
class LoyaltyTransactionResource extends JsonResource
{
    /**
     * Transform the loyalty transaction into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'loyalty_account_id' => $this->loyalty_account_id,
            'type'               => $this->type,
            'points'             => $this->points,
            'balance_after'      => $this->balance_after,
            'reference_type'     => $this->reference_type,
            'reference_id'       => $this->reference_id,
            'description'        => $this->description,
            'expires_at'         => $this->expires_at?->toIso8601String(),
            'created_by'         => $this->created_by,
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
