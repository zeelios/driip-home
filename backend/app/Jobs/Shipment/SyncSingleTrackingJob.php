<?php

declare(strict_types=1);

namespace App\Jobs\Shipment;

use App\Domain\Shipment\Actions\SyncTrackingAction;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync tracking for a single shipment.
 *
 * Delegates to SyncTrackingAction to fetch and persist
 * the latest tracking events from the courier.
 */
class SyncSingleTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Shipment $shipment
    ) {
    }

    public function handle(SyncTrackingAction $syncTracking): void
    {
        try {
            Log::info('Syncing tracking for shipment', [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'courier' => $this->shipment->courier_code,
            ]);

            $updated = $syncTracking->execute($this->shipment);

            Log::info('Tracking sync completed', [
                'shipment_id' => $updated->id,
                'new_status' => $updated->status,
                'events_synced' => $updated->trackingEvents()->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Tracking sync failed', [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
