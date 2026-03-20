<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Order\Actions\ConfirmOrderAction;
use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job to confirm multiple orders in bulk.
 *
 * Processes each order individually so that a single failure does not
 * prevent the remaining orders from being confirmed. All failures are
 * logged with context to aid debugging.
 */
class BulkConfirmOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new BulkConfirmOrdersJob.
     *
     * @param  list<string>  $orderIds  UUIDs of orders to confirm.
     * @param  string        $actorId   UUID of the staff member triggering the bulk action.
     * @param  string        $jobId     Unique job tracking identifier returned to the client.
     */
    public function __construct(
        private readonly array  $orderIds,
        private readonly string $actorId,
        private readonly string $jobId,
    ) {}

    /**
     * Execute the job.
     *
     * Loads each order and runs ConfirmOrderAction. Individual failures
     * are caught and logged without interrupting the batch.
     *
     * @param  ConfirmOrderAction  $action
     */
    public function handle(ConfirmOrderAction $action): void
    {
        $actor = User::find($this->actorId);

        if ($actor === null) {
            Log::error('BulkConfirmOrdersJob: actor not found.', ['actor_id' => $this->actorId, 'job_id' => $this->jobId]);
            return;
        }

        foreach ($this->orderIds as $orderId) {
            try {
                $order = Order::find($orderId);

                if ($order === null) {
                    Log::warning('BulkConfirmOrdersJob: order not found, skipping.', ['order_id' => $orderId]);
                    continue;
                }

                $action->execute($order, $actor);
            } catch (\Throwable $e) {
                Log::error('BulkConfirmOrdersJob: failed to confirm order.', [
                    'order_id' => $orderId,
                    'job_id'   => $this->jobId,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }
}
