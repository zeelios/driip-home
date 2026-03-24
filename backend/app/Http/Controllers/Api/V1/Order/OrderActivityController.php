<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Models\Order;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Order activity log controller.
 *
 * Provides read-only access to the complete audit trail of order events.
 */
class OrderActivityController extends BaseApiController
{
    /**
     * List all activities for an order with optional filtering.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, Order $order): AnonymousResourceCollection
    {
        $query = $order->activities()->latest('created_at');

        // Filter by activity type
        if ($request->has('type')) {
            $query->where('activity_type', $request->input('type'));
        }

        // Filter by actor type
        if ($request->has('actor')) {
            $query->where('actor_type', $request->input('actor'));
        }

        // Date range filter
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        return OrderActivityResource::collection(
            $query->paginate($request->input('per_page', 20))
        );
    }

    /**
     * Show a single activity record.
     *
     * @param  Order            $order
     * @param  string           $activityId
     * @return OrderActivityResource
     */
    public function show(Order $order, string $activityId): OrderActivityResource
    {
        $activity = $order->activities()->findOrFail($activityId);
        return OrderActivityResource::make($activity);
    }
}
