<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\UpdateVariantDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for updating an existing product variant.
 *
 * All fields are optional (patch semantics). SKU uniqueness is checked
 * at the Action layer so we only validate format here.
 */
class UpdateVariantRequest extends ApiRequest
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
     * Get the validation rules for updating a product variant.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'sku'              => ['sometimes', 'string', 'max:100'],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'attribute_values' => ['sometimes', 'array'],
            'compare_price'    => ['sometimes', 'integer', 'min:0'],
            'cost_price'       => ['sometimes', 'integer', 'min:0'],
            'selling_price'    => ['sometimes', 'integer', 'min:0'],
            'weight_grams'     => ['nullable', 'integer', 'min:1'],
            'status'           => ['nullable', 'in:active,inactive,out_of_stock'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'reason'           => ['nullable', 'string', 'max:500'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateVariantDto
    {
        return UpdateVariantDto::fromArray($this->validated());
    }

}
