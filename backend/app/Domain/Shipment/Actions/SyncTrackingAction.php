<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentTrackingEvent;
use App\Domain\Shipment\Services\CourierServiceResolver;
use Illuminate\Support\Facades\DB;

/**
 * Syncs the latest tracking events from the courier into the local database.
 *
 * Calls the appropriate CourierServiceInterface implementation, upserts
 * ShipmentTrackingEvent records keyed on (shipment_id, occurred_at, courier_status_code),
 * and updates the parent Shipment's status to reflect the most recent event.
 */
class SyncTrackingAction
{
    public function __construct(
        private readonly CourierServiceResolver $courierResolver
    ) {
    }

    /**
     * Status values in courier tracking responses that map to internal statuses.
     *
     * @var array<string,string>
     */
    private array $statusMap = [
        'created' => 'created',
        'ready_to_pick' => 'created',
        'picked_up' => 'picked_up',
        'in_transit' => 'in_transit',
        'out_for_delivery' => 'out_for_delivery',
        'delivered' => 'delivered',
        'failed_delivery' => 'failed_delivery',
        'returning' => 'returning',
        'returned' => 'returned',
        'cancelled' => 'cancelled',
    ];

    /**
     * Fetch and persist tracking events for the given shipment.
     *
     * Resolves the correct courier service, retrieves all tracking events,
     * upserts them into the database, and updates the shipment status.
     *
     * @param  Shipment  $shipment  The shipment whose tracking should be synced.
     * @return Shipment  The shipment with an updated status.
     *
     * @throws \RuntimeException  If no tracking number is set on the shipment.
     * @throws \Throwable         On any other failure.
     */
    public function execute(Shipment $shipment): Shipment
    {
        if (empty($shipment->tracking_number)) {
            throw new \RuntimeException('Cannot sync tracking: shipment has no tracking number.');
        }

        $courierService = $this->courierResolver->resolve($shipment->courier_code);

        $events = $courierService->getTrackingEvents($shipment->tracking_number);

        return DB::transaction(function () use ($shipment, $events): Shipment {
            $latestStatus = null;
            $latestOccurredAt = null;

            foreach ($events as $event) {
                ShipmentTrackingEvent::updateOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'occurred_at' => $event['occurred_at'],
                        'courier_status_code' => $event['courier_status_code'] ?? null,
                    ],
                    [
                        'status' => $event['status'],
                        'message' => $event['message'] ?? '',
                        'location' => $event['location'] ?? null,
                        'synced_at' => now(),
                        'raw_data' => $event,
                    ]
                );

                if ($latestOccurredAt === null || $event['occurred_at'] > $latestOccurredAt) {
                    $latestOccurredAt = $event['occurred_at'];
                    $latestStatus = $event['status'];
                }
            }

            if ($latestStatus !== null) {
                $internalStatus = $this->statusMap[$latestStatus] ?? $shipment->status;

                $updateData = ['status' => $internalStatus];

                if ($internalStatus === 'delivered' && $shipment->delivered_at === null) {
                    $updateData['delivered_at'] = now();
                }

                if ($internalStatus === 'failed_delivery') {
                    $updateData['failed_attempts'] = $shipment->failed_attempts + 1;
                }

                $shipment->update($updateData);
            }

            return $shipment->refresh();
        });
    }
}
