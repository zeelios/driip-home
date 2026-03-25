<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Domain\Staff\Data\CreateStaffDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new staff member.
 */
class CreateStaffRequest extends ApiRequest
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
     * Get the validation rules for creating a staff member.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'password'   => ['required', 'string', 'min:8'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'in:management,sales,warehouse,cs,marketing'],
            'position'   => ['nullable', 'string', 'max:100'],
            'hired_at'   => ['nullable', 'date'],
            'notes'      => ['nullable', 'string'],
            'roles'      => ['nullable', 'array'],
            'roles.*'    => ['string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sanitizeExcept(): array
    {
        return ['password'];
    }

    /**
     * Build the DTO from the validated request data.
     *
     * @return CreateStaffDto
     */
    public function dto(): CreateStaffDto
    {
        return CreateStaffDto::fromArray($this->validated());
    }
}
