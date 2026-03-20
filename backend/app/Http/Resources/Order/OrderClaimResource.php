<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for an order claim record.
 *
 * Exposes all claim fields including evidence URLs, resolution details,
 * and timestamps to support both staff and customer-facing views.
 *
 * @mixin \App\Domain\Order\Models\OrderClaim
 */
class OrderClaimResource extends JsonResource
{
    /**
     * Transform the order claim into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'claim_number'        => $this->claim_number,
            'order_id'            => $this->order_id,
            'order_item_id'       => $this->order_item_id,
            'type'                => $this->type,
            'status'              => $this->status,
            'description'         => $this->description,
            'evidence_urls'       => $this->evidence_urls,
            'resolution'          => $this->resolution,
            'resolution_notes'    => $this->resolution_notes,
            'refund_amount'       => $this->refund_amount,
            'assigned_to'         => $this->assigned_to,
            'created_by_customer' => $this->created_by_customer,
            'created_by'          => $this->created_by,
            'resolved_at'         => $this->resolved_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
