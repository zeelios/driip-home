<?php

declare(strict_types=1);

namespace App\Http\Resources\Shipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single COD remittance batch.
 *
 * Includes the full list of remittance line items when the items
 * relation has been eagerly loaded.
 *
 * @mixin \App\Domain\Shipment\Models\CourierCODRemittance
 */
class CourierCODRemittanceResource extends JsonResource
{
    /**
     * Transform the remittance into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'courier_code'          => $this->courier_code,
            'remittance_reference'  => $this->remittance_reference,
            'period_from'           => $this->period_from?->toDateString(),
            'period_to'             => $this->period_to?->toDateString(),
            'total_cod_collected'   => $this->total_cod_collected,
            'total_fees_deducted'   => $this->total_fees_deducted,
            'net_remittance'        => $this->net_remittance,
            'status'                => $this->status,
            'received_at'           => $this->received_at?->toIso8601String(),
            'notes'                 => $this->notes,
            'items'                 => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'shipment_id'  => $item->shipment_id,
                    'order_id'     => $item->order_id,
                    'cod_amount'   => $item->cod_amount,
                    'shipping_fee' => $item->shipping_fee,
                    'other_fees'   => $item->other_fees,
                    'net_amount'   => $item->net_amount,
                ]);
            }),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
