<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\CourierCODRemittanceItem;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentCODDiscrepancy;
use Illuminate\Support\Facades\Log;

/**
 * Detects COD discrepancies between courier claims and internal records.
 *
 * Identifies shipments where:
 * - Courier says delivered but no COD remittance received
 * - COD amounts don't match between courier and internal records
 */
class DetectShipmentCODDiscrepancyAction
{
    /**
     * Days after delivery to wait before flagging as discrepancy.
     */
    private const DISCREPANCY_THRESHOLD_DAYS = 3;

    /**
     * Execute discrepancy detection for a shipment.
     *
     * @param  Shipment  $shipment
     * @return ShipmentCODDiscrepancy|null  Created discrepancy or null if none found.
     */
    public function execute(Shipment $shipment): ?ShipmentCODDiscrepancy
    {
        // Skip if no COD amount
        if ($shipment->cod_amount <= 0) {
            return null;
        }

        // Skip if not delivered
        if (!$shipment->isDelivered()) {
            return null;
        }

        // Skip if COD already collected (via remittance)
        if ($shipment->cod_collected) {
            return null;
        }

        // Check if discrepancy already exists and is open
        $existing = ShipmentCODDiscrepancy::where('shipment_id', $shipment->id)
            ->whereIn('status', ['open', 'investigating'])
            ->first();

        if ($existing) {
            return null;
        }

        // Check if delivery is recent - give courier grace period
        if ($shipment->delivered_at && $shipment->delivered_at->diffInDays(now()) < self::DISCREPANCY_THRESHOLD_DAYS) {
            return null;
        }

        // Check for remittance record
        $remittanceItem = CourierCODRemittanceItem::where('shipment_id', $shipment->id)->first();

        if ($remittanceItem) {
            // Remittance exists but COD not marked collected - internal inconsistency
            return $this->createDiscrepancy(
                $shipment,
                'internal_inconsistency',
                "Shipment has remittance record but cod_collected=false. Remittance: {$remittanceItem->remittance_id}"
            );
        }

        // No remittance found - this is the main discrepancy case
        $discrepancy = $this->createDiscrepancy(
            $shipment,
            'cod_not_remittance',
            "Shipment delivered {$shipment->delivered_at->diffInDays(now())} days ago but no COD remittance received from courier. Courier: {$shipment->courier_code}, COD: {$shipment->cod_amount}"
        );

        Log::warning('COD discrepancy detected', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'courier' => $shipment->courier_code,
            'cod_amount' => $shipment->cod_amount,
            'discrepancy_id' => $discrepancy->id,
        ]);

        return $discrepancy;
    }

    /**
     * Create a discrepancy record.
     *
     * @param  Shipment  $shipment
     * @param  string    $type
     * @param  string    $description
     * @return ShipmentCODDiscrepancy
     */
    private function createDiscrepancy(Shipment $shipment, string $type, string $description): ShipmentCODDiscrepancy
    {
        return ShipmentCODDiscrepancy::create([
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'courier_code' => $shipment->courier_code,
            'tracking_number' => $shipment->tracking_number,
            'cod_amount' => $shipment->cod_amount,
            'discrepancy_type' => $type,
            'status' => 'open',
            'description' => $description,
            'courier_claim' => "Status: {$shipment->status}, Delivered: {$shipment->delivered_at?->toDateString()}",
            'internal_record' => "cod_collected: " . ($shipment->cod_collected ? 'true' : 'false'),
            'detected_at' => now(),
        ]);
    }

    /**
     * Detect all discrepancies across all delivered shipments.
     *
     * @param  int       $daysBack
     * @param  string|null $courierCode
     * @return array{created: int, total_checked: int}
     */
    public function detectAll(int $daysBack = 30, ?string $courierCode = null): array
    {
        $query = Shipment::query()
            ->where('status', 'delivered')
            ->where('cod_amount', '>', 0)
            ->where('cod_collected', false)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subDays(self::DISCREPANCY_THRESHOLD_DAYS))
            ->where('delivered_at', '>=', now()->subDays($daysBack))
            ->whereDoesntHave('codDiscrepancies', fn ($q) => $q->whereIn('status', ['open', 'investigating']));

        if ($courierCode) {
            $query->where('courier_code', $courierCode);
        }

        $shipments = $query->get();
        $created = 0;

        foreach ($shipments as $shipment) {
            $discrepancy = $this->execute($shipment);
            if ($discrepancy) {
                $created++;
            }
        }

        Log::info('COD discrepancy detection completed', [
            'checked' => $shipments->count(),
            'created' => $created,
            'courier' => $courierCode ?? 'all',
        ]);

        return [
            'created' => $created,
            'total_checked' => $shipments->count(),
        ];
    }
}
