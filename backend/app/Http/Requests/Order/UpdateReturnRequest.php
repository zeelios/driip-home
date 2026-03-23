<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\UpdateReturnDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating an existing order return record.
 *
 * Allows updating logistics (courier, tracking) and refund disposition
 * fields. All fields are optional for partial updates.
 */
class UpdateReturnRequest extends FormRequest
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
     * Get the validation rules for updating a return.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'status'           => ['sometimes', 'in:requested,approved,received,refunded,rejected'],
            'return_courier'   => ['nullable', 'string', 'max:100'],
            'return_tracking'  => ['nullable', 'string', 'max:100'],
            'total_refund'     => ['nullable', 'integer', 'min:0'],
            'refund_method'    => ['nullable', 'string', 'max:100'],
            'refund_reference' => ['nullable', 'string', 'max:100'],
            'refunded_at'      => ['nullable', 'date'],
            'received_at'      => ['nullable', 'date'],
            'processed_by'     => ['nullable', 'uuid'],
            'notes'            => ['nullable', 'string'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateReturnDto
    {
        return UpdateReturnDto::fromArray($this->validated());
    }

}
