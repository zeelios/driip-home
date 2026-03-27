<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for soft-deleting a product and cascading the deletion
 * to all of its variants.
 *
 * Both the product and its variants are soft-deleted within a single
 * database transaction to ensure atomicity.
 */
class DeleteProductAction
{
    /**
     * Soft-delete the product and all of its variants.
     *
     * @param  Product  $product  The product to delete.
     * @return void
     */
    public function execute(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->variants()->each(function (Product $variant): void {
                $variant->delete();
            });

            $product->delete();
        });
    }
}
