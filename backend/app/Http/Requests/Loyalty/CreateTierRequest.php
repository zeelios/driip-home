<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use App\Domain\Loyalty\Data\CreateTierDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new loyalty tier.
 */
class CreateTierRequest extends ApiRequest
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
     * Get the validation rules for creating a loyalty tier.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'slug'                => ['nullable', 'string', 'max:100', 'unique:loyalty_tiers,slug'],
            'min_lifetime_points' => ['required', 'integer', 'min:0'],
            'discount_percent'    => ['required', 'numeric', 'min:0', 'max:100'],
            'free_shipping'       => ['boolean'],
            'early_access'        => ['boolean'],
            'birthday_multiplier' => ['numeric', 'min:1'],
            'perks'               => ['nullable', 'array'],
            'color'               => ['nullable', 'string', 'max:7'],
            'sort_order'          => ['integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreateTierDto
    {
        return CreateTierDto::fromArray($this->validated());
    }

}
