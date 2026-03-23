<?php

declare(strict_types=1);

namespace App\Http\Resources\SaleEvent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for WaitlistEntry.
 *
 * @mixin \App\Domain\SaleEvent\Models\WaitlistEntry
 */
class WaitlistEntryResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'customer_id' => $this->customer_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'notified_at' => $this->notified_at,
            'created_at' => $this->created_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
