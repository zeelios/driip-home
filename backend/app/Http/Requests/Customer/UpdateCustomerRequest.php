<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Domain\Customer\Data\UpdateCustomerDto;
use App\Http\Requests\ApiRequest;

/**
 * Form request for updating an existing customer.
 *
 * All fields are optional; only provided fields will be updated.
 * Provides a typed DTO accessor for the validated payload.
 */
class UpdateCustomerRequest extends ApiRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for customer updates.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'      => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'gender'     => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
            'source'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes'      => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Build and return a typed UpdateCustomerDto from the validated input.
     *
     * @return UpdateCustomerDto
     */
    public function dto(): UpdateCustomerDto
    {
        return UpdateCustomerDto::fromArray($this->validated());
    }
}
