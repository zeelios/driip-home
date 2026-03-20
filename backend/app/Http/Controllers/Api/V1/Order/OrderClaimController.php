<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderClaim;
use App\Domain\Shared\Traits\GeneratesCode;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Order\CreateClaimRequest;
use App\Http\Resources\Order\OrderClaimResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manage claims raised against an order.
 *
 * Claims represent customer disputes (wrong item, damage, etc.) and
 * progress through a dedicated resolution workflow. All operations are
 * scoped to a parent order route parameter.
 */
class OrderClaimController extends BaseApiController
{
    use GeneratesCode;

    /**
     * List all claims for a given order.
     *
     * @param  Order  $order
     * @return AnonymousResourceCollection
     */
    public function index(Order $order): AnonymousResourceCollection
    {
        return OrderClaimResource::collection($order->claims()->latest()->get());
    }

    /**
     * Create a new claim against an order.
     *
     * @param  CreateClaimRequest  $request
     * @param  Order               $order
     * @return OrderClaimResource|JsonResponse
     */
    public function store(CreateClaimRequest $request, Order $order): OrderClaimResource|JsonResponse
    {
        try {
            $sequence = OrderClaim::count() + 1;

            $claim = $order->claims()->create([
                'claim_number'        => $this->buildCode('DRP-CLM', $sequence, 5),
                'order_item_id'       => $request->input('order_item_id'),
                'type'                => $request->input('type'),
                'status'              => 'open',
                'description'         => $request->input('description'),
                'evidence_urls'       => $request->input('evidence_urls', []),
                'created_by_customer' => false,
                'created_by'          => $request->user()?->id,
            ]);

            return OrderClaimResource::make($claim);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_CLAIM');
        }
    }

    /**
     * Show a single claim.
     *
     * @param  Order       $order
     * @param  OrderClaim  $claim
     * @return OrderClaimResource
     */
    public function show(Order $order, OrderClaim $claim): OrderClaimResource
    {
        return OrderClaimResource::make($claim);
    }

    /**
     * Update the status or resolution details of a claim.
     *
     * @param  Request     $request
     * @param  Order       $order
     * @param  OrderClaim  $claim
     * @return OrderClaimResource|JsonResponse
     */
    public function update(Request $request, Order $order, OrderClaim $claim): OrderClaimResource|JsonResponse
    {
        try {
            $claim->update($request->only([
                'status',
                'resolution',
                'resolution_notes',
                'refund_amount',
                'assigned_to',
            ]));

            if ($request->input('status') === 'resolved' && $claim->resolved_at === null) {
                $claim->update(['resolved_at' => now()]);
            }

            return OrderClaimResource::make($claim->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_CLAIM');
        }
    }
}
