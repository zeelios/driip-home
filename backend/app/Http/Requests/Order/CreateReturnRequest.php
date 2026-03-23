<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\CreateReturnDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for creating a new order return request.
 *
 * The return_items array must list the order item UUIDs and the quantities
 * being returned. An optional notes field captures customer-provided context.
 */
class CreateReturnRequest extends FormRequest
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
     * Get the validation rules for creating a return request.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'claim_id'                  => ['nullable', 'uuid'],
            'return_items'              => ['required', 'array', 'min:1'],
            'return_items.*.item_id'    => ['required', 'uuid'],
            'return_items.*.quantity'   => ['required', 'integer', 'min:1'],
            'return_items.*.reason'     => ['nullable', 'string'],
            'notes'                     => ['nullable', 'string'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreateReturnDto
    {
        return CreateReturnDto::fromArray($this->validated());
    }

}
