<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use App\Domain\Loyalty\Data\UpdateCampaignDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating an existing loyalty campaign.
 *
 * All fields are optional (PATCH semantics).
 */
class UpdateCampaignRequest extends ApiRequest
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
     * Get the validation rules for updating a loyalty campaign.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'type'         => ['sometimes', 'string', 'in:multiplier,flat_bonus,birthday,referral,first_order'],
            'multiplier'   => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'bonus_points' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'conditions'   => ['sometimes', 'nullable', 'array'],
            'starts_at'    => ['sometimes', 'date'],
            'ends_at'      => ['sometimes', 'nullable', 'date'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateCampaignDto
    {
        return UpdateCampaignDto::fromArray($this->validated());
    }

}
