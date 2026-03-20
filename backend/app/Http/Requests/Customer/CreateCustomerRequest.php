<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Domain\Customer\Data\CreateCustomerDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new customer.
 *
 * Validates the minimum required fields and provides a typed DTO accessor.
 */
class CreateCustomerRequest extends FormRequest
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
     * Get the validation rules for customer creation.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'gender'     => ['nullable', 'string', 'in:male,female,other'],
            'source'     => ['nullable', 'string', 'max:100'],
            'notes'      => ['nullable', 'string'],
        ];
    }

    /**
     * Build and return a typed CreateCustomerDto from the validated input.
     *
     * @return CreateCustomerDto
     */
    public function dto(): CreateCustomerDto
    {
        return CreateCustomerDto::fromArray($this->validated());
    }
}
