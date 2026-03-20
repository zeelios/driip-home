<?php

declare(strict_types=1);

namespace App\Http\Resources\Shipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a courier configuration.
 *
 * Sensitive credential fields (api_key, api_secret, webhook_secret) are
 * intentionally excluded from the serialised output.
 *
 * @mixin \App\Domain\Shipment\Models\CourierConfig
 */
class CourierConfigResource extends JsonResource
{
    /**
     * Transform the courier config into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'courier_code'     => $this->courier_code,
            'name'             => $this->name,
            'api_endpoint'     => $this->api_endpoint,
            'account_id'       => $this->account_id,
            'pickup_hub_code'  => $this->pickup_hub_code,
            'pickup_address'   => $this->pickup_address,
            'is_active'        => $this->is_active,
            'settings'         => $this->settings,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
