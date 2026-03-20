<?php

declare(strict_types=1);

namespace App\Http\Requests\SaleEvent;

use App\Domain\SaleEvent\Data\UpdateSaleEventDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for updating an existing sale event.
 *
 * All fields are optional (patch semantics). Slug uniqueness is checked
 * at the Action layer so we only validate format here.
 */
class UpdateSaleEventRequest extends FormRequest
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
     * Get the validation rules for updating a sale event.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'slug'             => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description'      => ['nullable', 'string'],
            'type'             => ['sometimes', 'in:flash_sale,drop_launch,clearance,bundle'],
            'status'           => ['sometimes', 'in:draft,scheduled,active,ended,cancelled'],
            'starts_at'        => ['sometimes', 'date'],
            'ends_at'          => ['nullable', 'date'],
            'max_orders_total' => ['nullable', 'integer', 'min:1'],
            'is_public'        => ['nullable', 'boolean'],
            'banner_url'       => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Build and return the UpdateSaleEventDto from the validated input.
     *
     * @return UpdateSaleEventDto
     */
    public function dto(): UpdateSaleEventDto
    {
        return UpdateSaleEventDto::fromArray($this->validated());
    }
}
