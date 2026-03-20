<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Data\CreateVariantDto;
use App\Domain\Product\Models\ProductPriceHistory;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for creating a new product variant and recording
 * its initial price in the price history ledger.
 *
 * Both the variant insert and the price history insert are wrapped in
 * a database transaction to keep them atomic.
 */
class CreateVariantAction
{
    /**
     * Execute the action: validate SKU uniqueness, create variant, record initial price.
     *
     * @param  CreateVariantDto  $dto  Validated variant data.
     * @return ProductVariant          The newly created variant instance.
     *
     * @throws ValidationException  If the SKU is already taken.
     */
    public function execute(CreateVariantDto $dto): ProductVariant
    {
        if (ProductVariant::withTrashed()->where('sku', $dto->sku)->exists()) {
            throw ValidationException::withMessages([
                'sku' => ["The SKU '{$dto->sku}' is already in use."],
            ]);
        }

        return DB::transaction(function () use ($dto): ProductVariant {
            $variant = ProductVariant::create([
                'product_id'       => $dto->productId,
                'sku'              => $dto->sku,
                'barcode'          => $dto->barcode,
                'attribute_values' => $dto->attributeValues,
                'compare_price'    => $dto->comparePrice,
                'cost_price'       => $dto->costPrice,
                'selling_price'    => $dto->sellingPrice,
                'weight_grams'     => $dto->weightGrams,
                'status'           => $dto->status,
            ]);

            ProductPriceHistory::create([
                'product_variant_id' => $variant->id,
                'compare_price'      => $dto->comparePrice,
                'cost_price'         => $dto->costPrice,
                'selling_price'      => $dto->sellingPrice,
                'changed_by'         => null,
                'reason'             => 'Initial price on variant creation.',
                'changed_at'         => now(),
            ]);

            return $variant;
        });
    }
}
