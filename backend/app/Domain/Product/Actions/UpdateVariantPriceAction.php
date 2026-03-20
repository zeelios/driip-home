<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\ProductPriceHistory;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for updating a variant's prices and appending a new
 * immutable entry to the price history ledger.
 *
 * Both operations are executed within a single database transaction so
 * that the variant and its history log remain in sync at all times.
 */
class UpdateVariantPriceAction
{
    /**
     * Execute the price update: save new prices and record the change.
     *
     * @param  ProductVariant  $variant       The variant whose prices are being updated.
     * @param  int             $comparePrice  New MSRP / strike-through price in VND.
     * @param  int             $costPrice     New landed cost price in VND.
     * @param  int             $sellingPrice  New standard retail price in VND.
     * @param  string|null     $changedBy     UUID of the staff user making the change.
     * @param  string|null     $reason        Human-readable explanation for the change.
     * @return ProductVariant                 The updated variant instance.
     */
    public function execute(
        ProductVariant $variant,
        int            $comparePrice,
        int            $costPrice,
        int            $sellingPrice,
        ?string        $changedBy = null,
        ?string        $reason    = null,
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $comparePrice,
            $costPrice,
            $sellingPrice,
            $changedBy,
            $reason,
        ): ProductVariant {
            $variant->update([
                'compare_price' => $comparePrice,
                'cost_price'    => $costPrice,
                'selling_price' => $sellingPrice,
            ]);

            ProductPriceHistory::create([
                'product_variant_id' => $variant->id,
                'compare_price'      => $comparePrice,
                'cost_price'         => $costPrice,
                'selling_price'      => $sellingPrice,
                'changed_by'         => $changedBy,
                'reason'             => $reason,
                'changed_at'         => now(),
            ]);

            return $variant->fresh();
        });
    }
}
