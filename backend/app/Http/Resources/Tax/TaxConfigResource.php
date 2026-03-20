<?php

declare(strict_types=1);

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single tax rate configuration.
 *
 * @mixin \App\Domain\Tax\Models\TaxConfig
 */
class TaxConfigResource extends JsonResource
{
    /**
     * Transform the tax config into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'rate'           => $this->rate,
            'applies_to'     => $this->applies_to,
            'applies_to_ids' => $this->applies_to_ids,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to'   => $this->effective_to?->toDateString(),
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
