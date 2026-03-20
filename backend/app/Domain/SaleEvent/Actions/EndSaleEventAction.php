<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Actions;

use App\Domain\SaleEvent\Models\SaleEvent;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for ending an active sale event.
 *
 * Sets the event status to 'ended' and clears the sale_price and
 * sale_event_id overrides from all participating product variants,
 * reverting them to their standard selling_price.
 *
 * All writes are wrapped in a database transaction to ensure the event
 * status update and all variant price reversals are applied atomically.
 */
class EndSaleEventAction
{
    /**
     * End the given sale event and revert variant sale price overrides.
     *
     * @param  SaleEvent  $saleEvent  The sale event to end.
     * @return SaleEvent              The refreshed, now-ended sale event.
     */
    public function execute(SaleEvent $saleEvent): SaleEvent
    {
        return DB::transaction(function () use ($saleEvent): SaleEvent {
            $saleEvent->update(['status' => 'ended']);

            foreach ($saleEvent->items()->with('variant')->get() as $item) {
                if ($item->variant === null) {
                    continue;
                }

                $item->variant->update([
                    'sale_price'    => null,
                    'sale_event_id' => null,
                ]);
            }

            return $saleEvent->fresh();
        });
    }
}
