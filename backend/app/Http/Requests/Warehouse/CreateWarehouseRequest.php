<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use App\Domain\Warehouse\Data\CreateWarehouseDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new warehouse.
 */
class CreateWarehouseRequest extends FormRequest
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
     * Get the validation rules for creating a warehouse.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'code'       => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', 'string', 'in:main,satellite,virtual,consignment'],
            'address'    => ['nullable', 'string'],
            'province'   => ['nullable', 'string', 'max:100'],
            'district'   => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'manager_id' => ['nullable', 'uuid'],
            'is_active'  => ['nullable', 'boolean'],
            'notes'      => ['nullable', 'string'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreateWarehouseDto
    {
        return CreateWarehouseDto::fromArray($this->validated());
    }

}
