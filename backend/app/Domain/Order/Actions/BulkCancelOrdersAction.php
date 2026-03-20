<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Staff\Models\User;
use App\Jobs\BulkCancelOrdersJob;
use Illuminate\Support\Str;

/**
 * Action to bulk-cancel multiple orders by dispatching a queued job.
 *
 * Delegates the actual per-order cancellation to BulkCancelOrdersJob
 * so the HTTP request returns immediately with a job identifier.
 */
class BulkCancelOrdersAction
{
    /**
     * Dispatch a bulk-cancel job for the given order IDs.
     *
     * @param  list<string>  $orderIds  UUIDs of orders to cancel.
     * @param  string        $reason    Human-readable cancellation reason applied to all orders.
     * @param  User          $actor     The staff member initiating the bulk action.
     * @return string                   The unique job ID for polling.
     */
    public function execute(array $orderIds, string $reason, User $actor): string
    {
        $jobId = (string) Str::uuid();

        BulkCancelOrdersJob::dispatch($orderIds, $reason, $actor->id, $jobId);

        return $jobId;
    }
}
