<?php

declare(strict_types=1);

namespace App\Http\Requests\SaleEvent;

use App\Domain\SaleEvent\Data\CreateSaleEventDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new sale event.
 */
class CreateSaleEventRequest extends ApiRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Permission checks are handled at the controller/policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a sale event.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', 'in:flash_sale,drop_launch,clearance,bundle'],
            'status'           => ['nullable', 'in:draft,scheduled'],
            'starts_at'        => ['required', 'date'],
            'ends_at'          => ['nullable', 'date', 'after:starts_at'],
            'max_orders_total' => ['nullable', 'integer', 'min:1'],
            'is_public'        => ['nullable', 'boolean'],
            'banner_url'       => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Build and return the CreateSaleEventDto from the validated input.
     *
     * @return CreateSaleEventDto
     */
    public function dto(): CreateSaleEventDto
    {
        /** @var string $userId */
        $userId = $this->user()?->id ?? '';

        return CreateSaleEventDto::fromArray($this->validated(), $userId);
    }
}
