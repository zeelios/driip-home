<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\StockCount;

/**
 * Action: mark a stock count as completed and calculate total variance.
 *
 * Transitions the status to 'completed', records the completed_at timestamp,
 * and aggregates total_variance_qty and total_variance_value from all counted items.
 */
class CompleteStockCountAction
{
    /**
     * Execute the stock count completion.
     *
     * @param  StockCount  $stockCount  The in-progress stock count to complete.
     *
     * @return StockCount  The updated stock count with items loaded.
     */
    public function execute(StockCount $stockCount): StockCount
    {
        $stockCount->loadMissing('items');

        $totalVarianceQty   = 0;
        $totalVarianceValue = 0;

        foreach ($stockCount->items as $item) {
            if ($item->variance !== null) {
                $totalVarianceQty   += abs($item->variance);
                $totalVarianceValue += abs($item->variance_value ?? 0);
            }
        }

        $stockCount->update([
            'status'               => 'completed',
            'completed_at'         => now(),
            'total_variance_qty'   => $totalVarianceQty,
            'total_variance_value' => $totalVarianceValue,
        ]);

        return $stockCount->fresh(['items', 'warehouse']);
    }
}
