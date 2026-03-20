<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\StockCountItem;
use App\Domain\Product\Models\ProductVariant;

/**
 * Action: record the physically counted quantity for a single stock count item.
 *
 * Updates the quantity_counted and calculates the variance
 * (counted - expected). Variance value is calculated based on the variant's cost price.
 * Also records the staff member who performed the count.
 */
class CountStockItemAction
{
    /**
     * Execute the count for a single stock count item.
     *
     * @param  StockCountItem  $item            The count item to update.
     * @param  int             $quantityCounted The physically counted quantity.
     * @param  string          $countedBy       UUID of the staff user performing the count.
     * @param  string|null     $notes           Optional notes for this count item.
     *
     * @return StockCountItem  The updated count item.
     */
    public function execute(
        StockCountItem $item,
        int            $quantityCounted,
        string         $countedBy,
        ?string        $notes = null,
    ): StockCountItem {
        $variance = $quantityCounted - $item->quantity_expected;

        // Calculate variance value based on product variant cost price.
        /** @var ProductVariant|null $variant */
        $variant       = ProductVariant::find($item->product_variant_id);
        $costPrice     = $variant?->cost_price ?? 0;
        $varianceValue = $variance * $costPrice;

        $item->update([
            'quantity_counted' => $quantityCounted,
            'variance'         => $variance,
            'variance_value'   => $varianceValue,
            'counted_by'       => $countedBy,
            'counted_at'       => now(),
            'notes'            => $notes ?? $item->notes,
        ]);

        // Transition stock count to in_progress when first item is counted.
        $stockCount = $item->stockCount;
        if ($stockCount->status === 'draft') {
            $stockCount->update([
                'status'     => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return $item->fresh();
    }
}
