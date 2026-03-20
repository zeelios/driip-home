<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use App\Domain\Shipment\Models\Shipment;

/**
 * Stub implementation of the GHN (Giao Hang Nhanh) courier integration.
 *
 * Returns mock data until the actual GHN API integration is implemented.
 * All methods follow the CourierServiceInterface contract so they can be
 * swapped in transparently via the service container.
 */
class GHNService implements CourierServiceInterface
{
    /**
     * Create a shipment on the GHN platform (stub).
     *
     * @param  Shipment  $shipment  The internal shipment model.
     * @return array<string,mixed>  Mock courier response.
     */
    public function createShipment(Shipment $shipment): array
    {
        return [
            'courier'          => 'ghn',
            'tracking_number'  => 'GHN' . strtoupper(substr(md5($shipment->id), 0, 10)),
            'label_url'        => null,
            'status'           => 'created',
            'estimated_fee'    => 0,
            'note'             => 'Stub — GHN API integration pending.',
        ];
    }

    /**
     * Retrieve tracking events for a shipment from GHN (stub).
     *
     * @param  string  $trackingNumber  The GHN tracking number.
     * @return array<int,array<string,mixed>>  Empty event list until real integration.
     */
    public function getTrackingEvents(string $trackingNumber): array
    {
        return [
            [
                'status'              => 'created',
                'courier_status_code' => 'ready_to_pick',
                'message'             => 'Shipment registered with GHN (stub).',
                'location'            => null,
                'occurred_at'         => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Cancel a shipment on the GHN platform (stub).
     *
     * @param  string  $trackingNumber  The GHN tracking number.
     * @return bool  Always returns true in stub mode.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        return true;
    }
}
