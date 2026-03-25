<?php

declare(strict_types=1);

namespace App\Jobs\Shipment;

use App\Domain\Shipment\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync tracking for all active shipments.
 *
 * Dispatches individual tracking sync jobs for shipments
 * that are in transit, pending, or awaiting delivery.
 */
class SyncAllTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?string $courierCode = null,
        private readonly int $daysBack = 30
    ) {
    }

    public function handle(): void
    {
        $syncedCount = 0;
        $failedCount = 0;

        // Get shipments needing tracking sync
        // Active statuses: created, picked_up, in_transit, out_for_delivery, pending
        $shipments = Shipment::query()
            ->whereIn('status', ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'pending'])
            ->whereNotNull('tracking_number')
            ->where('created_at', '>=', now()->subDays($this->daysBack))
            ->when($this->courierCode, fn ($q) => $q->where('courier_code', $this->courierCode))
            ->get();

        Log::info('Starting batch tracking sync', [
            'shipment_count' => $shipments->count(),
            'courier_code' => $this->courierCode,
            'days_back' => $this->daysBack,
        ]);

        foreach ($shipments as $shipment) {
            try {
                SyncSingleTrackingJob::dispatch($shipment);
                $syncedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error('Failed to dispatch tracking sync job', [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log the batch sync summary
        DB::table('shipment_sync_logs')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'sync_type' => 'batch',
            'courier_code' => $this->courierCode,
            'shipments_processed' => $syncedCount,
            'shipments_failed' => $failedCount,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Batch tracking sync completed', [
            'synced' => $syncedCount,
            'failed' => $failedCount,
        ]);
    }
}
