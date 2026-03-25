<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Actions\CancelOrderAction;
use App\Domain\Order\Actions\ConfirmOrderAction;
use App\Domain\Order\Actions\CreateOrderAction;
use App\Domain\Order\Actions\PackOrderAction;
use App\Domain\Order\Exceptions\InvalidOrderStatusTransitionException;
use App\Domain\Order\Exceptions\OrderNotCancellableException;
use App\Domain\Order\Models\Order;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Order\StatusHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage customer orders throughout their full lifecycle.
 *
 * Handles listing, creating, showing, updating, and performing status
 * transitions on orders. Bulk and document operations are delegated to
 * BulkOrderController and DocumentController respectively.
 */
class OrderController extends BaseApiController implements HasMiddleware
{
    /**
     * @param  CreateOrderAction   $createOrder   Creates a new order.
     * @param  ConfirmOrderAction  $confirmOrder  Confirms a pending order.
     * @param  PackOrderAction     $packOrder     Marks an order as packed.
     * @param  CancelOrderAction   $cancelOrder   Cancels a cancellable order.
     */
    public function __construct(
        private readonly CreateOrderAction $createOrder,
        private readonly ConfirmOrderAction $confirmOrder,
        private readonly PackOrderAction $packOrder,
        private readonly CancelOrderAction $cancelOrder,
    ) {
    }

    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view|orders.view.own', only: ['index']),
            new Middleware('permission:orders.view|orders.view.own', only: ['show']),
            new Middleware('permission:orders.create', only: ['store']),
            new Middleware('permission:orders.update|orders.update.own', only: ['update']),
            new Middleware('permission:orders.delete', only: ['destroy']),
            new Middleware('permission:orders.confirm', only: ['confirm']),
            new Middleware('permission:orders.pack', only: ['pack']),
            new Middleware('permission:orders.cancel', only: ['cancel']),
            new Middleware('permission:orders.view|orders.view.own', only: ['timeline']),
        ];
    }

    /**
     * List orders with filters, sorting and pagination.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = QueryBuilder::for(Order::class)
            ->allowedFilters(
                'status',
                'payment_status',
                'source',
                'customer_id',
                AllowedFilter::scope('created_from', 'createdFrom'),
                AllowedFilter::scope('created_to', 'createdTo'),
            )
            ->allowedSorts('created_at', 'total_after_tax', 'order_number')
            ->with(['customer'])
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    /**
     * Create a new order.
     *
     * @param  CreateOrderRequest  $request
     * @return OrderResource|JsonResponse
     */
    public function store(CreateOrderRequest $request): OrderResource|JsonResponse
    {
        try {
            $order = $this->createOrder->execute($request->dto());
            return OrderResource::make($order->load('customer'));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_ORDER');
        }
    }

    /**
     * Show a single order with related data.
     *
     * @param  Order  $order
     * @return OrderResource
     */
    public function show(Order $order): OrderResource
    {
        $order->load(['customer', 'items', 'statusHistory', 'claims', 'shipments']);
        return OrderResource::make($order);
    }

    /**
     * Update mutable order fields (notes, internal_notes, assigned_to).
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return OrderResource|JsonResponse
     */
    public function update(Request $request, Order $order): OrderResource|JsonResponse
    {
        try {
            $order->update($request->only(['notes', 'internal_notes', 'assigned_to']));
            return OrderResource::make($order->load('customer'));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_ORDER');
        }
    }

    /**
     * Soft-delete an order (admin only).
     *
     * @param  Order  $order
     * @return JsonResponse
     */
    public function destroy(Order $order): JsonResponse
    {
        try {
            $order->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_ORDER');
        }
    }

    /**
     * Confirm a pending order.
     *
     * @param  Order    $order
     * @param  Request  $request
     * @return OrderResource|JsonResponse
     */
    public function confirm(Order $order, Request $request): OrderResource|JsonResponse
    {
        try {
            $updated = $this->confirmOrder->execute($order, $request->user());
            return OrderResource::make($updated->load('customer'));
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->serverError($e, 'CONFIRM_ORDER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CONFIRM_ORDER');
        }
    }

    /**
     * Mark an order as packed.
     *
     * @param  Order    $order
     * @param  Request  $request
     * @return OrderResource|JsonResponse
     */
    public function pack(Order $order, Request $request): OrderResource|JsonResponse
    {
        try {
            $updated = $this->packOrder->execute($order, $request->user());
            return OrderResource::make($updated->load('customer'));
        } catch (InvalidOrderStatusTransitionException $e) {
            return $this->serverError($e, 'PACK_ORDER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'PACK_ORDER');
        }
    }

    /**
     * Cancel an order with a reason.
     *
     * @param  Order    $order
     * @param  Request  $request
     * @return OrderResource|JsonResponse
     */
    public function cancel(Order $order, Request $request): OrderResource|JsonResponse
    {
        try {
            $updated = $this->cancelOrder->execute(
                $order,
                (string) $request->input('reason', ''),
                $request->user()
            );
            return OrderResource::make($updated->load('customer'));
        } catch (OrderNotCancellableException $e) {
            return $this->serverError($e, 'CANCEL_ORDER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CANCEL_ORDER');
        }
    }

    /**
     * Return the full status history timeline for an order.
     *
     * @param  Order  $order
     * @return AnonymousResourceCollection
     */
    public function timeline(Order $order): AnonymousResourceCollection
    {
        $history = $order->statusHistory()->orderBy('created_at')->get();
        return StatusHistoryResource::collection($history);
    }
}
