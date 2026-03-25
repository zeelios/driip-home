<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\UpdateBrandDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating an existing brand.
 *
 * All fields are optional (patch semantics). Slug uniqueness is checked
 * at the Action layer so we only validate format here.
 */
class UpdateBrandRequest extends ApiRequest
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
     * Get the validation rules for updating a brand.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'logo_url'    => ['nullable', 'string', 'max:500'],
            'website'     => ['nullable', 'string', 'max:255'],
            'country'     => ['nullable', 'string', 'max:10'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateBrandDto
    {
        return UpdateBrandDto::fromArray($this->validated());
    }

}
