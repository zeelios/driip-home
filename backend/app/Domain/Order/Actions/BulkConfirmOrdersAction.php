<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Staff\Models\User;
use App\Jobs\BulkConfirmOrdersJob;
use Illuminate\Support\Str;

/**
 * Action to bulk-confirm multiple orders by dispatching a queued job.
 *
 * Delegates the actual per-order confirmation to BulkConfirmOrdersJob
 * so the HTTP request returns immediately with a job identifier.
 */
class BulkConfirmOrdersAction
{
    /**
     * Dispatch a bulk-confirm job for the given order IDs.
     *
     * @param  list<string>  $orderIds  UUIDs of orders to confirm.
     * @param  User          $actor     The staff member initiating the bulk action.
     * @return string                   The unique job ID for polling.
     */
    public function execute(array $orderIds, User $actor): string
    {
        $jobId = (string) Str::uuid();

        BulkConfirmOrdersJob::dispatch($orderIds, $actor->id, $jobId);

        return $jobId;
    }
}
