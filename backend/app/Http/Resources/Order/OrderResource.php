<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a full order record.
 *
 * Includes pricing, shipping, status, and optional related resources
 * (items and status history) when they have been eager-loaded.
 *
 * @mixin \App\Domain\Order\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the order into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'customer'        => $this->when(
                $this->customer !== null,
                fn () => [
                    'id'            => $this->customer->id,
                    'customer_code' => $this->customer->customer_code,
                    'full_name'     => $this->customer->fullName(),
                    'phone'         => $this->customer->phone,
                    'email'         => $this->customer->email,
                ]
            ),
            'guest_name'      => $this->when($this->customer === null, $this->guest_name),
            'guest_email'     => $this->when($this->customer === null, $this->guest_email),
            'guest_phone'     => $this->when($this->customer === null, $this->guest_phone),
            'status'          => $this->status,
            'payment_status'  => $this->payment_status,
            'payment_method'  => $this->payment_method,
            'subtotal'        => $this->subtotal,
            'coupon_code'     => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
            'loyalty_discount' => $this->loyalty_discount,
            'shipping_fee'    => $this->shipping_fee,
            'vat_rate'        => $this->vat_rate,
            'vat_amount'      => $this->vat_amount,
            'total_before_tax' => $this->total_before_tax,
            'total_after_tax' => $this->total_after_tax,
            'shipping_name'   => $this->shipping_name,
            'shipping_phone'  => $this->shipping_phone,
            'shipping_province' => $this->shipping_province,
            'shipping_district' => $this->shipping_district,
            'shipping_ward'   => $this->shipping_ward,
            'shipping_address' => $this->shipping_address,
            'notes'           => $this->notes,
            'source'          => $this->source,
            'tags'            => $this->tags,
            'created_at'      => $this->created_at?->toIso8601String(),
            'confirmed_at'    => $this->confirmed_at?->toIso8601String(),
            'delivered_at'    => $this->delivered_at?->toIso8601String(),
            'cancelled_at'    => $this->cancelled_at?->toIso8601String(),
            'items'           => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history'  => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),
        ];
    }
}
