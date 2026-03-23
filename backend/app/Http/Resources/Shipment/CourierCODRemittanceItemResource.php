<?php

declare(strict_types=1);

namespace App\Http\Resources\Shipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for CourierCODRemittanceItem.
 *
 * @mixin \App\Domain\Shipment\Models\CourierCODRemittanceItem
 */
class CourierCODRemittanceItemResource extends JsonResource
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
            'remittance_id' => $this->remittance_id,
            'shipment_id' => $this->shipment_id,
            'order_id' => $this->order_id,
            'cod_amount' => $this->cod_amount,
            'shipping_fee' => $this->shipping_fee,
            'other_fees' => $this->other_fees,
            'net_amount' => $this->net_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
