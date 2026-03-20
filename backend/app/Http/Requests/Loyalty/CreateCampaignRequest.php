<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new loyalty campaign.
 */
class CreateCampaignRequest extends FormRequest
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
     * Get the validation rules for creating a loyalty campaign.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'type'         => ['required', 'string', 'in:multiplier,flat_bonus,birthday,referral,first_order'],
            'multiplier'   => ['nullable', 'numeric', 'min:1'],
            'bonus_points' => ['nullable', 'integer', 'min:0'],
            'conditions'   => ['nullable', 'array'],
            'starts_at'    => ['required', 'date'],
            'ends_at'      => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'    => ['boolean'],
        ];
    }
}
