<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Order\Actions\CancelOrderAction;
use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job to cancel multiple orders in bulk.
 *
 * Processes each order individually so that a single failure does not
 * prevent the remaining orders from being cancelled. All failures are
 * logged with context to aid debugging.
 */
class BulkCancelOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new BulkCancelOrdersJob.
     *
     * @param  list<string>  $orderIds  UUIDs of orders to cancel.
     * @param  string        $reason    Cancellation reason applied to all orders.
     * @param  string        $actorId   UUID of the staff member triggering the bulk action.
     * @param  string        $jobId     Unique job tracking identifier returned to the client.
     */
    public function __construct(
        private readonly array  $orderIds,
        private readonly string $reason,
        private readonly string $actorId,
        private readonly string $jobId,
    ) {}

    /**
     * Execute the job.
     *
     * Loads each order and runs CancelOrderAction. Individual failures
     * are caught and logged without interrupting the batch.
     *
     * @param  CancelOrderAction  $action
     */
    public function handle(CancelOrderAction $action): void
    {
        $actor = User::find($this->actorId);

        if ($actor === null) {
            Log::error('BulkCancelOrdersJob: actor not found.', ['actor_id' => $this->actorId, 'job_id' => $this->jobId]);
            return;
        }

        foreach ($this->orderIds as $orderId) {
            try {
                $order = Order::find($orderId);

                if ($order === null) {
                    Log::warning('BulkCancelOrdersJob: order not found, skipping.', ['order_id' => $orderId]);
                    continue;
                }

                $action->execute($order, $this->reason, $actor);
            } catch (\Throwable $e) {
                Log::error('BulkCancelOrdersJob: failed to cancel order.', [
                    'order_id' => $orderId,
                    'job_id'   => $this->jobId,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }
}
