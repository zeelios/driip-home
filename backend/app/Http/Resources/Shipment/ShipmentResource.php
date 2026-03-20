<?php

declare(strict_types=1);

namespace App\Http\Resources\Shipment;

use App\Http\Resources\Staff\StaffResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single shipment.
 *
 * Includes a basic order summary, all tracking events, and the staff member
 * who created the shipment when those relations are eagerly loaded.
 *
 * @mixin \App\Domain\Shipment\Models\Shipment
 */
class ShipmentResource extends JsonResource
{
    /**
     * Transform the shipment into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'order_id'              => $this->order_id,
            'order'                 => $this->whenLoaded('order', fn () => [
                'id'           => $this->order->id,
                'order_number' => $this->order->order_number ?? null,
                'status'       => $this->order->status      ?? null,
            ]),
            'courier_code'          => $this->courier_code,
            'tracking_number'       => $this->tracking_number,
            'internal_reference'    => $this->internal_reference,
            'status'                => $this->status,
            'label_url'             => $this->label_url,
            'cod_amount'            => $this->cod_amount,
            'cod_collected'         => $this->cod_collected,
            'shipping_fee_quoted'   => $this->shipping_fee_quoted,
            'shipping_fee_actual'   => $this->shipping_fee_actual,
            'weight_kg'             => $this->weight_kg,
            'estimated_delivery_at' => $this->estimated_delivery_at?->toDateString(),
            'delivered_at'          => $this->delivered_at?->toIso8601String(),
            'failed_attempts'       => $this->failed_attempts,
            'tracking_events'       => $this->whenLoaded(
                'trackingEvents',
                fn () => ShipmentTrackingEventResource::collection($this->trackingEvents)
            ),
            'created_by'            => $this->whenLoaded(
                'createdBy',
                fn () => $this->createdBy !== null ? new StaffResource($this->createdBy) : null
            ),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
