<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new coupon.
 */
class CreateCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Permission checks are handled at the controller/policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a coupon.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'code'                  => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'name'                  => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'type'                  => ['required', 'in:percent,fixed_amount,free_shipping'],
            'value'                 => ['required', 'numeric', 'min:0'],
            'min_order_amount'      => ['nullable', 'integer', 'min:0'],
            'min_items'             => ['nullable', 'integer', 'min:1'],
            'max_discount_amount'   => ['nullable', 'integer', 'min:0'],
            'applies_to'            => ['nullable', 'in:all,category,product,brand'],
            'applies_to_ids'        => ['nullable', 'array'],
            'applies_to_ids.*'      => ['uuid'],
            'max_uses'              => ['nullable', 'integer', 'min:1'],
            'max_uses_per_customer' => ['nullable', 'integer', 'min:1'],
            'is_public'             => ['nullable', 'boolean'],
            'is_active'             => ['nullable', 'boolean'],
            'starts_at'             => ['nullable', 'date'],
            'expires_at'            => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
