<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use App\Domain\Warehouse\Data\AssignStaffDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for assigning a staff member to a warehouse.
 */
class AssignStaffRequest extends ApiRequest
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
     * Get the validation rules for a warehouse staff assignment.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'role'    => ['required', 'string', 'max:50'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): AssignStaffDto
    {
        return AssignStaffDto::fromArray($this->validated());
    }

}
