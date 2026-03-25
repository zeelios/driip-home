<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Domain\Staff\Data\CreateSalaryDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a salary record for a staff member.
 */
class CreateSalaryRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Always returns true — permission checks are handled at the controller level.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a salary record.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'period'             => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'base_salary'        => ['required', 'integer', 'min:0'],
            'allowances'         => ['nullable', 'array'],
            'allowances.*'       => ['numeric'],
            'bonuses'            => ['nullable', 'array'],
            'bonuses.*'          => ['numeric'],
            'deductions'         => ['nullable', 'array'],
            'deductions.*'       => ['numeric'],
            'overtime_hours'     => ['nullable', 'numeric', 'min:0'],
            'overtime_rate'      => ['nullable', 'integer', 'min:0'],
            'payment_method'     => ['nullable', 'string', 'max:100'],
            'payment_reference'  => ['nullable', 'string', 'max:255'],
            'paid_at'            => ['nullable', 'date'],
            'notes'              => ['nullable', 'string'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreateSalaryDto
    {
        return CreateSalaryDto::fromArray($this->validated());
    }

}
