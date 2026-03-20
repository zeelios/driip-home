<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Domain\Coupon\Data\ValidateCouponDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for checking coupon validity at checkout.
 */
class ValidateCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Coupon validation is a public-facing action (guest checkout supported).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for a coupon validation check.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'code'         => ['required', 'string', 'max:50'],
            'customer_id'  => ['nullable', 'uuid'],
            'order_amount' => ['required', 'integer', 'min:0'],
            'item_count'   => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Build and return the ValidateCouponDto from the validated input.
     *
     * @return ValidateCouponDto
     */
    public function dto(): ValidateCouponDto
    {
        return ValidateCouponDto::fromArray($this->validated());
    }
}
