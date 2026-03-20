<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Product\Data\CreateVariantDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new product variant.
 */
class CreateVariantRequest extends FormRequest
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
     * Get the validation rules for creating a product variant.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'sku'              => ['required', 'string', 'max:100'],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'attribute_values' => ['required', 'array'],
            'compare_price'    => ['required', 'integer', 'min:0'],
            'cost_price'       => ['required', 'integer', 'min:0'],
            'selling_price'    => ['required', 'integer', 'min:0'],
            'weight_grams'     => ['nullable', 'integer', 'min:1'],
            'status'           => ['nullable', 'in:active,inactive,discontinued'],
        ];
    }

    /**
     * Build and return the CreateVariantDto from the validated input.
     *
     * The product UUID is taken from the route parameter.
     *
     * @return CreateVariantDto
     */
    public function dto(): CreateVariantDto
    {
        /** @var string $productId */
        $productId = $this->route('product')?->id ?? $this->route('product');

        return CreateVariantDto::fromArray($productId, $this->validated());
    }
}
