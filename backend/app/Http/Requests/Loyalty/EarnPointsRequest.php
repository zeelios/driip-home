<?php

declare(strict_types=1);

namespace App\Http\Requests\Loyalty;

use App\Domain\Loyalty\Data\EarnPointsDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for crediting loyalty points to a customer's account.
 */
class EarnPointsRequest extends FormRequest
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
     * Get the validation rules for earning loyalty points.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'points'         => ['required', 'integer', 'min:1'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'uuid'],
            'description'    => ['nullable', 'string', 'max:500'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): EarnPointsDto
    {
        return EarnPointsDto::fromArray($this->validated());
    }

}
