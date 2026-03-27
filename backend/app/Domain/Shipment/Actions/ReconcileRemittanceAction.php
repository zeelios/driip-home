<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Data\ReconcileRemittanceDto;
use App\Domain\Shipment\Models\CourierCODRemittance;
use App\Domain\Shipment\Models\CourierCODRemittanceItem;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles a courier COD remittance batch against internal shipments.
 *
 * Iterates each line item from the courier's remittance statement,
 * matches it to an internal Shipment by tracking_number, creates or updates
 * CourierCODRemittanceItem records, marks matched shipments as cod_collected,
 * and recalculates the remittance totals.
 */
class ReconcileRemittanceAction
{
    /**
     * Perform reconciliation for the given remittance batch.
     *
     * For each item in the DTO:
     *  1. Look up the internal Shipment by tracking_number.
     *  2. Create a CourierCODRemittanceItem record with amounts.
     *  3. Mark the Shipment's cod_collected = true.
     * Then recalculate totals on the remittance and set status to 'received'.
     *
     * @param  CourierCODRemittance    $remittance  The remittance batch to reconcile.
     * @param  ReconcileRemittanceDto  $dto         Line items from the courier statement.
     * @return CourierCODRemittance  The updated remittance with items loaded.
     *
     * @throws \Throwable On any database failure.
     */
    public function execute(CourierCODRemittance $remittance, ReconcileRemittanceDto $dto): CourierCODRemittance
    {
        return DB::transaction(function () use ($remittance, $dto): CourierCODRemittance {
            $totalCodCollected = 0;
            $totalFeesDeducted = 0;

            foreach ($dto->items as $itemData) {
                $shipment = Shipment::where('tracking_number', $itemData['tracking_number'])
                    ->where('courier_code', $remittance->courier_code)
                    ->first();

                if ($shipment === null) {
                    continue;
                }

                $codAmount = $itemData['cod_amount'];
                $shippingFee = $itemData['shipping_fee'];
                $otherFees = 0;
                $netAmount = $codAmount - $shippingFee - $otherFees;

                CourierCODRemittanceItem::updateOrCreate(
                    [
                        'remittance_id' => $remittance->id,
                        'shipment_id' => $shipment->id,
                    ],
                    [
                        'order_id' => $shipment->order_id,
                        'cod_amount' => $codAmount,
                        'shipping_fee' => $shippingFee,
                        'other_fees' => $otherFees,
                        'net_amount' => $netAmount,
                    ]
                );

                /** @var Shipment $shipment */
                $shipment->update(['cod_collected' => true]);

                $totalCodCollected += $codAmount;
                $totalFeesDeducted += $shippingFee;
            }

            $remittance->update([
                'total_cod_collected' => $totalCodCollected,
                'total_fees_deducted' => $totalFeesDeducted,
                'net_remittance' => $totalCodCollected - $totalFeesDeducted,
                'status' => 'received',
            ]);

            return $remittance->load('items')->refresh();
        });
    }
}
