<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for updating an existing warehouse.
 */
class UpdateWarehouseRequest extends FormRequest
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
     * Get the validation rules for updating a warehouse.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'type'       => ['sometimes', 'string', 'in:main,satellite,dropship'],
            'address'    => ['nullable', 'string'],
            'province'   => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'manager_id' => ['nullable', 'uuid'],
            'is_active'  => ['sometimes', 'boolean'],
            'notes'      => ['nullable', 'string'],
        ];
    }

    /**
     * Build an UpdateWarehouseDto from the validated request data.
     *
     * @return \App\Domain\Warehouse\Data\UpdateWarehouseDto
     */
    public function dto(): \App\Domain\Warehouse\Data\UpdateWarehouseDto
    {
        return new \App\Domain\Warehouse\Data\UpdateWarehouseDto(
            name:       $this->input('name'),
            type:       $this->input('type'),
            address:    $this->input('address'),
            province:   $this->input('province'),
            phone:      $this->input('phone'),
            managerId:  $this->input('manager_id'),
            isActive:   $this->has('is_active') ? (bool) $this->input('is_active') : null,
            notes:      $this->input('notes'),
        );
    }
}
