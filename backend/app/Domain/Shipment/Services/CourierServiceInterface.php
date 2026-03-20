<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use App\Domain\Shipment\Models\Shipment;

/**
 * Contract that every courier integration service must implement.
 *
 * Provides a uniform interface for creating shipments on the courier
 * platform, fetching live tracking events, and cancelling shipments.
 */
interface CourierServiceInterface
{
    /**
     * Create a shipment record on the courier's platform and return the raw response.
     *
     * @param  Shipment  $shipment  The internal shipment model with all required data.
     * @return array<string,mixed>  Courier API response payload.
     */
    public function createShipment(Shipment $shipment): array;

    /**
     * Fetch all tracking events for a given tracking number from the courier.
     *
     * @param  string  $trackingNumber  The courier-assigned tracking number.
     * @return array<int,array<string,mixed>>  List of raw tracking event records.
     */
    public function getTrackingEvents(string $trackingNumber): array;

    /**
     * Request cancellation of a shipment on the courier's platform.
     *
     * @param  string  $trackingNumber  The courier-assigned tracking number.
     * @return bool  True if the cancellation was accepted by the courier.
     */
    public function cancelShipment(string $trackingNumber): bool;
}
