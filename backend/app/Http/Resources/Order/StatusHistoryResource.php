<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single order status history entry.
 *
 * @mixin \App\Domain\Order\Models\OrderStatusHistory
 */
class StatusHistoryResource extends JsonResource
{
    /**
     * Transform the status history entry into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'from_status'         => $this->from_status,
            'to_status'           => $this->to_status,
            'note'                => $this->note,
            'is_customer_visible' => $this->is_customer_visible,
            'created_by'          => $this->created_by,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
