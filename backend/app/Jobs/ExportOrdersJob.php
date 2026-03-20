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
 * Queued job to export orders to a downloadable file.
 *
 * Full export implementation (CSV/XLSX generation, storage, notification)
 * is deferred to a later phase. This stub logs that the job was received.
 */
class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new ExportOrdersJob.
     *
     * @param  array<string,mixed>  $filters  Request filters defining the export scope.
     * @param  string               $actorId  UUID of the staff member requesting the export.
     * @param  string               $jobId    Unique job tracking identifier returned to the client.
     */
    public function __construct(
        private readonly array  $filters,
        private readonly string $actorId,
        private readonly string $jobId,
    ) {}

    /**
     * Execute the job.
     *
     * Stubbed implementation. Full export logic is deferred.
     */
    public function handle(): void
    {
        Log::info('ExportOrdersJob: received — full implementation deferred.', [
            'actor_id' => $this->actorId,
            'job_id'   => $this->jobId,
            'filters'  => $this->filters,
        ]);
    }
}
