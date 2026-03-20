<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for applying a manual inventory adjustment.
 */
class AdjustInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller / policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for an inventory adjustment.
     *
     * quantity must be a non-zero integer (positive to add, negative to remove).
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'variant_id'   => ['required', 'uuid'],
            'warehouse_id' => ['required', 'uuid'],
            'quantity'     => ['required', 'integer', 'not_in:0'],
            'reason'       => ['required', 'string', 'max:500'],
        ];
    }
}
