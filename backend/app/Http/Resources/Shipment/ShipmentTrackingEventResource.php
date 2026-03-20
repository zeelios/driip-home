<?php

declare(strict_types=1);

namespace App\Http\Resources\Shipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single shipment tracking event.
 *
 * @mixin \App\Domain\Shipment\Models\ShipmentTrackingEvent
 */
class ShipmentTrackingEventResource extends JsonResource
{
    /**
     * Transform the tracking event into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'shipment_id'         => $this->shipment_id,
            'status'              => $this->status,
            'courier_status_code' => $this->courier_status_code,
            'message'             => $this->message,
            'location'            => $this->location,
            'occurred_at'         => $this->occurred_at?->toIso8601String(),
            'synced_at'           => $this->synced_at?->toIso8601String(),
        ];
    }
}
