<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Domain\Staff\Data\UpdateStaffDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating an existing staff member.
 *
 * All fields are optional, allowing partial updates. Password changes
 * are handled through a separate dedicated endpoint.
 */
class UpdateStaffRequest extends ApiRequest
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
     * Get the validation rules for updating a staff member.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', 'max:255'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'department' => ['sometimes', 'nullable', 'in:management,sales,warehouse,cs,marketing'],
            'position'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'     => ['sometimes', 'in:active,inactive,terminated'],
            'hired_at'   => ['sometimes', 'nullable', 'date'],
            'notes'      => ['sometimes', 'nullable', 'string'],
            'roles'      => ['sometimes', 'nullable', 'array'],
            'roles.*'    => ['string'],
        ];
    }

    /**
     * Build the DTO from the validated request data.
     *
     * @return UpdateStaffDto
     */
    public function dto(): UpdateStaffDto
    {
        return UpdateStaffDto::fromArray($this->validated());
    }
}
