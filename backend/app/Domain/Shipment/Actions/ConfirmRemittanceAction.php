<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\CourierCODRemittance;

/**
 * Marks a COD remittance batch as fully reconciled.
 *
 * Should be called after the finance team has verified the remittance
 * amounts match internal records. Transitions the remittance status
 * from any prior state to 'reconciled' and records the received_at timestamp
 * if not already set.
 */
class ConfirmRemittanceAction
{
    /**
     * Confirm and close out the given remittance batch.
     *
     * Sets status to 'reconciled' and records the current timestamp as
     * received_at if it has not already been set.
     *
     * @param  CourierCODRemittance  $remittance  The remittance batch to confirm.
     * @return CourierCODRemittance  The updated remittance record.
     */
    public function execute(CourierCODRemittance $remittance): CourierCODRemittance
    {
        $remittance->update([
            'status'      => 'reconciled',
            'received_at' => $remittance->received_at ?? now(),
        ]);

        return $remittance->refresh();
    }
}
