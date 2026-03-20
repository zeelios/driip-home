<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for an order return record.
 *
 * Exposes all return fields including shipment tracking, refund details,
 * and processing timestamps.
 *
 * @mixin \App\Domain\Order\Models\OrderReturn
 */
class OrderReturnResource extends JsonResource
{
    /**
     * Transform the order return into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'return_number'    => $this->return_number,
            'order_id'         => $this->order_id,
            'claim_id'         => $this->claim_id,
            'status'           => $this->status,
            'return_items'     => $this->return_items,
            'return_courier'   => $this->return_courier,
            'return_tracking'  => $this->return_tracking,
            'total_refund'     => $this->total_refund,
            'refund_method'    => $this->refund_method,
            'refund_reference' => $this->refund_reference,
            'refunded_at'      => $this->refunded_at?->toIso8601String(),
            'received_at'      => $this->received_at?->toIso8601String(),
            'processed_by'     => $this->processed_by,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
