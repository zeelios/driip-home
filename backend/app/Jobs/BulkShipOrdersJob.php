<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job to bulk-ship multiple orders via a specified courier.
 *
 * Full shipment creation logic is deferred to a later implementation phase.
 * This stub dispatches the job to the queue and logs receipt.
 */
class BulkShipOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new BulkShipOrdersJob.
     *
     * @param  list<string>  $orderIds    UUIDs of orders to ship.
     * @param  string        $courierCode Courier service identifier.
     * @param  string        $actorId     UUID of the staff member triggering the action.
     * @param  string        $jobId       Unique job tracking identifier returned to the client.
     */
    public function __construct(
        private readonly array  $orderIds,
        private readonly string $courierCode,
        private readonly string $actorId,
        private readonly string $jobId,
    ) {}

    /**
     * Execute the job.
     *
     * Stubbed implementation. Full courier integration is deferred.
     */
    public function handle(): void
    {
        Log::info('BulkShipOrdersJob: received.', [
            'count'        => count($this->orderIds),
            'courier_code' => $this->courierCode,
            'actor_id'     => $this->actorId,
            'job_id'       => $this->jobId,
        ]);
    }
}
