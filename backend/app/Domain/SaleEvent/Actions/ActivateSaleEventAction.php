<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Actions;

use App\Domain\Product\Models\ProductVariant;
use App\Domain\SaleEvent\Models\SaleEvent;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for activating a draft or scheduled sale event.
 *
 * Sets the event status to 'active' and, for every participating item,
 * writes the item's sale_price and the event's UUID directly onto the
 * product_variants table. This allows the checkout and product listing
 * layers to read effective prices without joining back to sale event tables.
 *
 * All writes are wrapped in a database transaction to ensure the event
 * status and all variant price overrides are applied atomically.
 */
class ActivateSaleEventAction
{
    /**
     * Activate the given sale event and push sale prices to all variants.
     *
     * @param  SaleEvent  $saleEvent  The sale event to activate.
     * @return SaleEvent              The refreshed, now-active sale event.
     */
    public function execute(SaleEvent $saleEvent): SaleEvent
    {
        return DB::transaction(function () use ($saleEvent): SaleEvent {
            $saleEvent->update(['status' => 'active']);

            foreach ($saleEvent->items()->with('variant')->get() as $item) {
                if ($item->variant === null) {
                    continue;
                }

                $item->variant->update([
                    'sale_price'    => $item->sale_price,
                    'sale_event_id' => $saleEvent->id,
                ]);
            }

            return $saleEvent->fresh();
        });
    }
}
