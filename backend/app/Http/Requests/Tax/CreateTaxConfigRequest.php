<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use App\Domain\Tax\Data\CreateTaxConfigDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the payload for creating a new tax rate configuration.
 */
class CreateTaxConfigRequest extends ApiRequest
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
     * Get the validation rules for creating a tax configuration.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'rate'           => ['required', 'numeric', 'min:0', 'max:100'],
            'applies_to'     => ['nullable', 'in:all,category,product'],
            'applies_to_ids' => ['nullable', 'array'],
            'applies_to_ids.*' => ['string'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    /**
     * Build a CreateTaxConfigDto from the validated request data.
     *
     * @return CreateTaxConfigDto
     */
    public function dto(): CreateTaxConfigDto
    {
        return CreateTaxConfigDto::fromArray($this->validated());
    }
}
