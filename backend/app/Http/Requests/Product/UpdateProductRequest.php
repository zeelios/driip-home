<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\UpdateProductDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for updating an existing product.
 *
 * All fields are optional (patch semantics). Slug uniqueness is checked
 * at the Action layer.
 */
class UpdateProductRequest extends FormRequest
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
     * Get the validation rules for updating a product.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:255'],
            'slug'              => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'brand_id'          => ['nullable', 'uuid', 'exists:brands,id'],
            'category_id'       => ['nullable', 'uuid', 'exists:categories,id'],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'sku_base'          => ['nullable', 'string', 'max:50'],
            'gender'            => ['nullable', 'in:men,women,unisex,kids'],
            'season'            => ['nullable', 'string', 'max:20'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string'],
            'status'            => ['nullable', 'in:draft,active,archived'],
            'is_featured'       => ['nullable', 'boolean'],
            'published_at'      => ['nullable', 'date'],
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string'],
        ];
    }

    /**
     * Build and return the UpdateProductDto from the validated input.
     *
     * @return UpdateProductDto
     */
    public function dto(): UpdateProductDto
    {
        return UpdateProductDto::fromArray($this->validated());
    }
}
