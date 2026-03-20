<?php

declare(strict_types=1);

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single coupon.
 *
 * The created_by field (staff user UUID) is exposed to allow admin UIs to
 * display authorship. No sensitive internal fields are suppressed beyond
 * the default model hidden list.
 *
 * @mixin \App\Domain\Coupon\Models\Coupon
 */
class CouponResource extends JsonResource
{
    /**
     * Transform the coupon into an array for the API response.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'code'                  => $this->code,
            'name'                  => $this->name,
            'description'           => $this->description,
            'type'                  => $this->type,
            'value'                 => $this->value,
            'min_order_amount'      => $this->min_order_amount,
            'min_items'             => $this->min_items,
            'max_discount_amount'   => $this->max_discount_amount,
            'applies_to'            => $this->applies_to,
            'applies_to_ids'        => $this->applies_to_ids,
            'max_uses'              => $this->max_uses,
            'max_uses_per_customer' => $this->max_uses_per_customer,
            'used_count'            => $this->used_count,
            'is_public'             => $this->is_public,
            'is_active'             => $this->is_active,
            'is_valid'              => $this->isValid(),
            'is_expired'            => $this->isExpired(),
            'starts_at'             => $this->starts_at?->toIso8601String(),
            'expires_at'            => $this->expires_at?->toIso8601String(),
            'created_by'            => $this->created_by,
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
