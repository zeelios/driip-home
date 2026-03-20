<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Data\UpdateProductDto;
use App\Domain\Product\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing product's fields.
 *
 * If the DTO contains a slug that differs from the current one, it is
 * validated for uniqueness across all products (including soft-deleted).
 */
class UpdateProductAction
{
    /**
     * Execute the product update.
     *
     * @param  UpdateProductDto  $dto      Validated partial product data.
     * @param  Product           $product  The product model to update.
     * @return Product                      The refreshed product instance.
     *
     * @throws ValidationException  If the provided slug is already taken by another product.
     */
    public function execute(UpdateProductDto $dto, Product $product): Product
    {
        $updateData = $dto->toUpdateArray();

        if (isset($updateData['slug']) && $updateData['slug'] !== $product->slug) {
            $conflictExists = Product::withTrashed()
                ->where('slug', $updateData['slug'])
                ->where('id', '!=', $product->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'slug' => ["The slug '{$updateData['slug']}' is already in use."],
                ]);
            }
        }

        $product->update($updateData);

        return $product->fresh()->load(['brand', 'category']);
    }
}
