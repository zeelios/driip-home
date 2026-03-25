<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\CreateCategoryDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new category.
 */
class CreateCategoryRequest extends ApiRequest
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
     * Get the validation rules for creating a category.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'image_url'   => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreateCategoryDto
    {
        return CreateCategoryDto::fromArray($this->validated());
    }

}
