<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\ProductPriceHistory;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing product variant.
 *
 * Handles both non-price attribute updates and price changes. When price
 * fields are modified, the operation is wrapped in a transaction and a
 * price history record is appended to preserve the audit trail.
 */
class UpdateVariantAction
{
    /**
     * Execute the variant update.
     *
     * If any price field (compare_price, cost_price, selling_price) changes,
     * a ProductPriceHistory entry is inserted inside the same transaction.
     *
     * @param  ProductVariant       $variant   The variant to update.
     * @param  array<string,mixed>  $data      Validated partial variant data.
     * @param  string|null          $changedBy UUID of the staff user making the change.
     * @return ProductVariant                   The refreshed variant instance.
     *
     * @throws ValidationException  If the provided SKU is already taken by another variant.
     */
    public function execute(ProductVariant $variant, array $data, ?string $changedBy = null): ProductVariant
    {
        if (isset($data['sku']) && $data['sku'] !== $variant->sku) {
            $conflictExists = ProductVariant::withTrashed()
                ->where('sku', $data['sku'])
                ->where('id', '!=', $variant->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'sku' => ["The SKU '{$data['sku']}' is already in use."],
                ]);
            }
        }

        $priceFields  = ['compare_price', 'cost_price', 'selling_price'];
        $hasPriceChange = collect($priceFields)->some(
            fn (string $f) => array_key_exists($f, $data)
        );

        if ($hasPriceChange) {
            return DB::transaction(function () use ($variant, $data, $changedBy, $priceFields): ProductVariant {
                $comparePrice  = (int) ($data['compare_price'] ?? $variant->compare_price);
                $costPrice     = (int) ($data['cost_price'] ?? $variant->cost_price);
                $sellingPrice  = (int) ($data['selling_price'] ?? $variant->selling_price);

                $variant->update(array_merge(
                    array_diff_key($data, array_flip(['reason'])),
                    [
                        'compare_price' => $comparePrice,
                        'cost_price'    => $costPrice,
                        'selling_price' => $sellingPrice,
                    ]
                ));

                ProductPriceHistory::create([
                    'product_variant_id' => $variant->id,
                    'compare_price'      => $comparePrice,
                    'cost_price'         => $costPrice,
                    'selling_price'      => $sellingPrice,
                    'changed_by'         => $changedBy,
                    'reason'             => $data['reason'] ?? 'Manual price update.',
                    'changed_at'         => now(),
                ]);

                return $variant->fresh();
            });
        }

        $variant->update(array_diff_key($data, array_flip(['reason'])));

        return $variant->fresh();
    }
}
