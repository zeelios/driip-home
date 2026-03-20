<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderReturn;
use App\Domain\Shared\Traits\GeneratesCode;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderReturnResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manage physical return requests associated with an order.
 *
 * Returns track the logistics and refund lifecycle of goods sent back
 * by a customer. All operations are scoped to a parent order.
 */
class OrderReturnController extends BaseApiController
{
    use GeneratesCode;

    /**
     * List all returns for a given order.
     *
     * @param  Order  $order
     * @return AnonymousResourceCollection
     */
    public function index(Order $order): AnonymousResourceCollection
    {
        return OrderReturnResource::collection($order->returns()->latest()->get());
    }

    /**
     * Create a new return request for an order.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return OrderReturnResource|JsonResponse
     */
    public function store(Request $request, Order $order): OrderReturnResource|JsonResponse
    {
        try {
            $sequence = OrderReturn::count() + 1;

            $return = $order->returns()->create([
                'return_number' => $this->buildCode('DRP-RET', $sequence, 5),
                'claim_id'      => $request->input('claim_id'),
                'status'        => 'requested',
                'return_items'  => $request->input('return_items', []),
                'notes'         => $request->input('notes'),
            ]);

            return OrderReturnResource::make($return);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_RETURN');
        }
    }

    /**
     * Show a single return record.
     *
     * @param  Order        $order
     * @param  OrderReturn  $return
     * @return OrderReturnResource
     */
    public function show(Order $order, OrderReturn $return): OrderReturnResource
    {
        return OrderReturnResource::make($return);
    }

    /**
     * Update a return record's status, tracking, or refund details.
     *
     * @param  Request      $request
     * @param  Order        $order
     * @param  OrderReturn  $return
     * @return OrderReturnResource|JsonResponse
     */
    public function update(Request $request, Order $order, OrderReturn $return): OrderReturnResource|JsonResponse
    {
        try {
            $return->update($request->only([
                'status',
                'return_courier',
                'return_tracking',
                'total_refund',
                'refund_method',
                'refund_reference',
                'refunded_at',
                'received_at',
                'processed_by',
                'notes',
            ]));

            return OrderReturnResource::make($return->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_RETURN');
        }
    }
}
