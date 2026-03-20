<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Data\CreateProductDto;
use App\Domain\Product\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for persisting a new product to the database.
 *
 * Validates that the slug is unique before insertion and throws a
 * ValidationException if a conflict is detected so the API layer can
 * surface a 422 response.
 */
class CreateProductAction
{
    /**
     * Execute the action: validate the slug and create the product.
     *
     * @param  CreateProductDto  $dto  Validated product data.
     * @return Product                 The newly created product instance.
     *
     * @throws ValidationException  If the slug is already taken.
     */
    public function execute(CreateProductDto $dto): Product
    {
        if (Product::withTrashed()->where('slug', $dto->slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["The slug '{$dto->slug}' is already in use."],
            ]);
        }

        return Product::create([
            'brand_id'          => $dto->brandId,
            'category_id'       => $dto->categoryId,
            'name'              => $dto->name,
            'slug'              => $dto->slug,
            'description'       => $dto->description,
            'short_description' => $dto->shortDescription,
            'sku_base'          => $dto->skuBase,
            'gender'            => $dto->gender,
            'season'            => $dto->season,
            'tags'              => $dto->tags,
            'status'            => $dto->status,
        ]);
    }
}
