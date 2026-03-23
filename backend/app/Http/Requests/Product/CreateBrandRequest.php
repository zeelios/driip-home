<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\CreateBrandDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new brand.
 */
class CreateBrandRequest extends FormRequest
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
     * Get the validation rules for creating a brand.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
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
    public function dto(): CreateBrandDto
    {
        return CreateBrandDto::fromArray($this->validated());
    }

}
