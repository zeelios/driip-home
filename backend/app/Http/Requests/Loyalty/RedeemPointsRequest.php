<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for redeeming loyalty points from a customer's account.
 */
class RedeemPointsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for redeeming loyalty points.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'points'      => ['required', 'integer', 'min:1'],
            'reference_id' => ['nullable', 'uuid', 'exists:orders,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
