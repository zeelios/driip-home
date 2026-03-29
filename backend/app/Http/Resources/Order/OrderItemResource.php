<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single order line item.
 *
 * Represents one physical product instance with size, inventory, and shipment tracking.
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
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'size_option_id' => $this->size_option_id,
            'size_display' => $this->when(
                $this->sizeOption !== null,
                fn() => $this->sizeOption?->display_name ?? $this->sizeOption?->code
            ),
            'color' => $this->color,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'status' => $this->status,
            'picked_at' => $this->picked_at?->toIso8601String(),
            'picked_by' => $this->picked_by,
            'picked_by_name' => $this->when($this->pickedBy !== null, fn() => $this->pickedBy?->name),
            'packed_at' => $this->packed_at?->toIso8601String(),
            'packed_by' => $this->packed_by,
            'packed_by_name' => $this->when($this->packedBy !== null, fn() => $this->packedBy?->name),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'inventory_id' => $this->inventory_id,
            'inventory' => $this->when(
                $this->inventory !== null,
                fn() => [
                    'id' => $this->inventory->id,
                    'warehouse_id' => $this->inventory->warehouse_id,
                    'quantity_on_hand' => $this->inventory->quantity_on_hand,
                ]
            ),
            'shipment_id' => $this->shipment_id,
            'shipment' => $this->when(
                $this->shipment !== null,
                fn() => [
                    'id' => $this->shipment->id,
                    'tracking_number' => $this->shipment->tracking_number,
                    'status' => $this->shipment->status,
                    'courier_code' => $this->shipment->courier_code,
                ]
            ),
            'order' => $this->when(
                $this->order !== null,
                fn() => [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'created_at' => $this->order->created_at?->toIso8601String(),
                    'shipping_name' => $this->order->shipping_name,
                    'shipping_province' => $this->order->shipping_province,
                ]
            ),
        ];
    }
}
