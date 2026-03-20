<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating mutable fields on an existing order.
 *
 * Only the fields that are safe to change post-creation are exposed here.
 * Status transitions must go through the dedicated action endpoints.
 */
class UpdateOrderRequest extends FormRequest
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
     * Get the validation rules for updating an order.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'notes'          => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'assigned_to'    => ['nullable', 'uuid'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string'],
        ];
    }
}
