<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\CreateProductDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new product.
 */
class CreateProductRequest extends FormRequest
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
     * Get the validation rules for creating a product.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
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
        ];
    }

    /**
     * Build and return the CreateProductDto from the validated input.
     *
     * @return CreateProductDto
     */
    public function dto(): CreateProductDto
    {
        return CreateProductDto::fromArray($this->validated());
    }
}
