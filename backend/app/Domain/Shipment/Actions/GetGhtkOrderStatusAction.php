<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentTrackingEvent;
use App\Domain\Shipment\Services\GHTKService;
use Illuminate\Support\Facades\Log;

/**
 * Action to get GHTK order status and sync tracking.
 */
class GetGhtkOrderStatusAction
{
    public function __construct(
        private readonly GHTKService $ghtkService
    ) {
    }

    /**
     * Get order status from GHTK.
     *
     * @param  string  $trackingNumber
     * @return array<string,mixed>
     *
     * @throws \RuntimeException
     */
    public function execute(string $trackingNumber): array
    {
        return $this->ghtkService->getOrderStatus($trackingNumber);
    }

    /**
     * Sync tracking status to shipment record.
     *
     * @param  Shipment  $shipment
     * @return Shipment  Updated shipment.
     */
    public function syncToShipment(Shipment $shipment): Shipment
    {
        if (empty($shipment->tracking_number)) {
            throw new \RuntimeException('Shipment has no tracking number');
        }

        $status = $this->execute($shipment->tracking_number);

        // Update shipment status
        $shipment->update([
            'status' => $this->mapGhtkStatus($status['status']),
            'courier_status' => $status['status'],
            'delivered_at' => $this->parseDeliverDate($status['deliver_date']),
            'picked_up_at' => $this->parsePickDate($status['pick_date']),
        ]);

        // Create tracking event
        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $shipment->status,
            'courier_status_code' => $status['status'],
            'courier_status_text' => $status['status_text'],
            'message' => $status['message'] ?: $status['status_text'],
            'location' => $status['address'],
            'occurred_at' => now(),
            'metadata' => [
                'ship_money' => $status['ship_money'],
                'insurance' => $status['insurance'],
                'weight' => $status['weight'],
            ],
        ]);

        Log::info('GHTK order status synced', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
        ]);

        return $shipment->refresh();
    }

    /**
     * Map GHTK status code to internal status.
     *
     * @param  string|null  $ghtkStatus
     * @return string
     */
    private function mapGhtkStatus(?string $ghtkStatus): string
    {
        $map = [
            '-1' => 'cancelled',
            '1' => 'created',
            '2' => 'created',
            '3' => 'picked_up',
            '4' => 'in_transit',
            '5' => 'in_transit',
            '6' => 'out_for_delivery',
            '7' => 'failed_delivery',
            '8' => 'out_for_delivery',
            '9' => 'delivered',
            '10' => 'returning',
            '11' => 'returned',
            '12' => 'pending',
            '13' => 'returning',
            '20' => 'reconciled',
            '21' => 'reconciled',
            '22' => 'reconciled',
            '31' => 'failed_delivery',
        ];

        return $map[$ghtkStatus] ?? 'unknown';
    }

    /**
     * Parse delivery date string.
     *
     * @param  string|null  $date
     * @return \Carbon\Carbon|null
     */
    private function parseDeliverDate(?string $date): ?\Carbon\Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parse pick date string.
     *
     * @param  string|null  $date
     * @return \Carbon\Carbon|null
     */
    private function parsePickDate(?string $date): ?\Carbon\Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception) {
            return null;
        }
    }
}
