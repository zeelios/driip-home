<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Jobs\BulkCancelOrdersJob;
use App\Jobs\BulkConfirmOrdersJob;
use App\Jobs\BulkShipOrdersJob;
use App\Jobs\ExportOrdersJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Handle bulk order operations that are processed asynchronously via queued jobs.
 *
 * Each endpoint accepts an array of order IDs, dispatches the appropriate
 * job to the queue, and immediately returns a queued acknowledgement to
 * the caller.
 */
class BulkOrderController extends BaseApiController
{
    /**
     * Bulk-confirm multiple orders by dispatching a queued job.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $orderIds = (array) $request->input('order_ids', []);
            $jobId    = (string) Str::uuid();

            BulkConfirmOrdersJob::dispatch($orderIds, $request->user()->id, $jobId);

            return response()->json(['queued' => true, 'job_id' => $jobId, 'count' => count($orderIds)]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'BULK_CONFIRM_ORDERS');
        }
    }

    /**
     * Bulk-ship multiple orders by dispatching a queued job.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function ship(Request $request): JsonResponse
    {
        try {
            $orderIds    = (array) $request->input('order_ids', []);
            $courierCode = (string) $request->input('courier_code', '');
            $jobId       = (string) Str::uuid();

            BulkShipOrdersJob::dispatch($orderIds, $courierCode, $request->user()->id, $jobId);

            return response()->json(['queued' => true, 'job_id' => $jobId, 'count' => count($orderIds)]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'BULK_SHIP_ORDERS');
        }
    }

    /**
     * Bulk-cancel multiple orders by dispatching a queued job.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $orderIds = (array) $request->input('order_ids', []);
            $reason   = (string) $request->input('reason', '');
            $jobId    = (string) Str::uuid();

            BulkCancelOrdersJob::dispatch($orderIds, $reason, $request->user()->id, $jobId);

            return response()->json(['queued' => true, 'job_id' => $jobId, 'count' => count($orderIds)]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'BULK_CANCEL_ORDERS');
        }
    }

    /**
     * Queue an order export job and return an acknowledgement.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $jobId = (string) Str::uuid();

            ExportOrdersJob::dispatch($request->all(), $request->user()->id, $jobId);

            return response()->json(['queued' => true, 'job_id' => $jobId]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'EXPORT_ORDERS');
        }
    }
}
