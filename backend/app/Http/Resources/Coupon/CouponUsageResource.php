<?php

declare(strict_types=1);

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for CouponUsage.
 *
 * @mixin \App\Domain\Coupon\Models\CouponUsage
 */
class CouponUsageResource extends JsonResource
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
            'coupon_id' => $this->coupon_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'discount_amount' => $this->discount_amount,
            'used_at' => $this->used_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
