<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use App\Domain\Loyalty\Data\UpdateTierDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating an existing loyalty tier.
 *
 * All fields are optional (PATCH semantics).
 */
class UpdateTierRequest extends ApiRequest
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
     * Get the validation rules for updating a loyalty tier.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $tierId = $this->route('loyalty_tier')?->id ?? $this->route('loyaltyTier')?->id;

        return [
            'name'                => ['sometimes', 'string', 'max:100'],
            'slug'                => ['sometimes', 'string', 'max:100', 'unique:loyalty_tiers,slug,' . $tierId],
            'min_lifetime_points' => ['sometimes', 'integer', 'min:0'],
            'discount_percent'    => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'free_shipping'       => ['sometimes', 'boolean'],
            'early_access'        => ['sometimes', 'boolean'],
            'birthday_multiplier' => ['sometimes', 'numeric', 'min:1'],
            'perks'               => ['sometimes', 'nullable', 'array'],
            'color'               => ['sometimes', 'nullable', 'string', 'max:7'],
            'sort_order'          => ['sometimes', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateTierDto
    {
        return UpdateTierDto::fromArray($this->validated());
    }

}
