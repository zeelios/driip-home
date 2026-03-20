<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single order line item.
 *
 * @mixin \App\Domain\Order\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * Transform the order item into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'sku'               => $this->sku,
            'name'              => $this->name,
            'size'              => $this->size,
            'color'             => $this->color,
            'unit_price'        => $this->unit_price,
            'quantity'          => $this->quantity,
            'discount_amount'   => $this->discount_amount,
            'total_price'       => $this->total_price,
            'quantity_returned' => $this->quantity_returned,
        ];
    }
}
