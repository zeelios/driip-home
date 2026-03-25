<?php

declare(strict_types=1);

namespace App\Http\Requests\Shipment;

use App\Domain\Shipment\Data\UpdateCourierConfigDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating a courier configuration.
 */
class UpdateCourierConfigRequest extends ApiRequest
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
     * Get the validation rules for updating a courier config.
     *
     * All fields are optional (PATCH semantics).
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:100'],
            'api_endpoint'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'api_key'          => ['sometimes', 'nullable', 'string'],
            'api_secret'       => ['sometimes', 'nullable', 'string'],
            'account_id'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'pickup_hub_code'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'pickup_address'   => ['sometimes', 'nullable', 'array'],
            'webhook_secret'   => ['sometimes', 'nullable', 'string'],
            'is_active'        => ['sometimes', 'boolean'],
            'settings'         => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sanitizeExcept(): array
    {
        return [
            'api_key',
            'api_secret',
            'webhook_secret',
            'settings.*',
        ];
    }

    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateCourierConfigDto
    {
        return UpdateCourierConfigDto::fromArray($this->validated());
    }

}
