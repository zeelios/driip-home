<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use App\Domain\Order\Models\OrderActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for order activity log entries.
 */
class OrderActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderActivity $this */
        return [
            'id'              => $this->id,
            'order_id'        => $this->order_id,
            'actor_type'      => $this->actor_type,
            'actor_id'        => $this->actor_id,
            'activity_type'   => $this->activity_type,
            'description'     => $this->description,
            'metadata'        => $this->metadata,
            'ip_address'      => $this->ip_address,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
