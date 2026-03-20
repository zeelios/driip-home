<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Domain\Coupon\Data\UpdateCouponDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for updating an existing coupon.
 *
 * All fields are optional (patch semantics). Code uniqueness is checked
 * at the Action layer so we only validate format here.
 */
class UpdateCouponRequest extends FormRequest
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
     * Get the validation rules for updating a coupon.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'code'                  => ['sometimes', 'string', 'max:50'],
            'name'                  => ['sometimes', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'type'                  => ['sometimes', 'in:percent,fixed_amount,free_shipping'],
            'value'                 => ['sometimes', 'numeric', 'min:0'],
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
            'expires_at'            => ['nullable', 'date'],
        ];
    }

    /**
     * Build and return the UpdateCouponDto from the validated input.
     *
     * @return UpdateCouponDto
     */
    public function dto(): UpdateCouponDto
    {
        return UpdateCouponDto::fromArray($this->validated());
    }
}
