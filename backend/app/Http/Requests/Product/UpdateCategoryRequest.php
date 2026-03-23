<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\UpdateCategoryDto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for updating an existing category.
 *
 * All fields are optional (patch semantics). Slug uniqueness is checked
 * at the Action layer so we only validate format here.
 */
class UpdateCategoryRequest extends FormRequest
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
     * Get the validation rules for updating a category.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'image_url'   => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateCategoryDto
    {
        return UpdateCategoryDto::fromArray($this->validated());
    }

}
