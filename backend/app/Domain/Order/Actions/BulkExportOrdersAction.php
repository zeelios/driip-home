<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Staff\Models\User;
use App\Jobs\ExportOrdersJob;
use Illuminate\Support\Str;

/**
 * Action to export orders by dispatching a queued export job.
 *
 * Delegates the actual export generation to ExportOrdersJob so the
 * HTTP request returns immediately with a job identifier. The caller
 * can use the job ID to poll for completion and retrieve the export URL.
 */
class BulkExportOrdersAction
{
    /**
     * Dispatch an export job for the given filters.
     *
     * @param  array<string,mixed>  $filters  Query filters to scope the export (e.g. status, date range).
     * @param  User                 $actor    The staff member requesting the export.
     * @return string               The unique job ID for polling.
     */
    public function execute(array $filters, User $actor): string
    {
        $jobId = (string) Str::uuid();

        ExportOrdersJob::dispatch($filters, $actor->id, $jobId);

        return $jobId;
    }
}
