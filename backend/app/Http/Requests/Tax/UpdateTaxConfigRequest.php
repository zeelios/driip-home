<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use App\Domain\Tax\Data\UpdateTaxConfigDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the payload for updating an existing tax rate configuration.
 *
 * All fields are optional to support partial updates.
 */
class UpdateTaxConfigRequest extends ApiRequest
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
     * Get the validation rules for updating a tax configuration.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'string', 'max:100'],
            'rate'           => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'applies_to'     => ['sometimes', 'in:all,category,product'],
            'applies_to_ids' => ['sometimes', 'nullable', 'array'],
            'applies_to_ids.*' => ['string'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to'   => ['sometimes', 'nullable', 'date'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateTaxConfigDto
    {
        return UpdateTaxConfigDto::fromArray($this->validated());
    }

}
