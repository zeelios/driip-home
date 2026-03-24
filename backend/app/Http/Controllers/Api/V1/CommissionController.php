<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Commission\Models\CommissionConfig;
use App\Domain\Commission\Services\CommissionCalculator;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Services\OrderActivityLogger;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

/**
 * Commission management controller.
 *
 * Handles commission calculations, approvals, and payouts.
 */
class CommissionController extends BaseApiController
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
        private readonly OrderActivityLogger $activityLogger
    ) {}

    /**
     * Get commission summary for a staff member.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|uuid|exists:users,id',
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            $summary = $this->calculator->getSummaryForStaff(
                $request->input('staff_id'),
                $request->input('from'),
                $request->input('to')
            );

            return response()->json(['data' => $summary]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'COMMISSION_SUMMARY');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COMMISSION_SUMMARY');
        }
    }

    /**
     * List orders with commission for a staff member.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function orders(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'nullable|uuid|exists:users,id',
            'status'   => 'nullable|in:pending,approved,paid,cancelled',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            $query = Order::whereNotNull('sales_rep_id')
                ->where('commission_amount', '>', 0);

            if ($request->has('staff_id')) {
                $query->where('sales_rep_id', $request->input('staff_id'));
            }

            if ($request->has('status')) {
                $query->where('commission_status', $request->input('status'));
            }

            return OrderResource::collection(
                $query->latest()->paginate($request->input('per_page', 20))
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'COMMISSION_ORDERS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COMMISSION_ORDERS');
        }
    }

    /**
     * Approve commission for an order.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse|OrderResource
     */
    public function approve(Request $request, Order $order): JsonResponse|OrderResource
    {
        try {
            if ($order->commission_status !== 'pending') {
                return response()->json([
                    'error' => 'Commission must be in pending status to approve',
                ], 422);
            }

            $order->update(['commission_status' => 'approved']);

            $this->activityLogger->log(
                $order,
                'commission_approved',
                "Commission of {$order->commission_amount} approved",
                [
                    'commission_amount' => $order->commission_amount,
                    'commission_rate'   => $order->commission_rate,
                ],
                $request->user()
            );

            return OrderResource::make($order->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'APPROVE_COMMISSION');
        }
    }

    /**
     * Mark commission as paid.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse|OrderResource
     */
    public function markPaid(Request $request, Order $order): JsonResponse|OrderResource
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            if (!in_array($order->commission_status, ['pending', 'approved'], true)) {
                return response()->json([
                    'error' => 'Commission must be pending or approved to mark as paid',
                ], 422);
            }

            $reference = $request->input('reference');

            $order->update([
                'commission_status'        => 'paid',
                'commission_paid_reference'=> $reference,
                'commission_paid_at'     => now(),
            ]);

            $this->activityLogger->logCommissionPaid(
                $order,
                $order->commission_amount,
                $reference,
                $request->user()
            );

            return OrderResource::make($order->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'MARK_COMMISSION_PAID');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'MARK_COMMISSION_PAID');
        }
    }

    /**
     * Cancel commission for an order.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse|OrderResource
     */
    public function cancel(Request $request, Order $order): JsonResponse|OrderResource
    {
        try {
            if ($order->commission_status === 'paid') {
                return response()->json([
                    'error' => 'Cannot cancel already paid commission',
                ], 422);
            }

            $order->update(['commission_status' => 'cancelled']);

            $this->activityLogger->log(
                $order,
                'commission_cancelled',
                "Commission of {$order->commission_amount} cancelled",
                [
                    'commission_amount' => $order->commission_amount,
                ],
                $request->user()
            );

            return OrderResource::make($order->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CANCEL_COMMISSION');
        }
    }

    /**
     * Create or update commission config for a staff member.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id'       => 'required|uuid|exists:users,id',
            'rate_percent'   => 'required|numeric|min:0|max:100',
            'category_rates' => 'nullable|array',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            // Deactivate existing configs for this staff
            CommissionConfig::where('staff_id', $request->input('staff_id'))
                ->where('is_active', true)
                ->update(['is_active' => false, 'effective_to' => now()->subDay()->toDateString()]);

            // Create new config
            $config = CommissionConfig::create([
                'staff_id'       => $request->input('staff_id'),
                'rate_percent'   => $request->input('rate_percent'),
                'category_rates' => $request->input('category_rates', []),
                'effective_from' => $request->input('effective_from'),
                'effective_to'   => $request->input('effective_to'),
                'is_active'      => true,
            ]);

            return response()->json(['data' => $config], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'STORE_COMMISSION_CONFIG');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'STORE_COMMISSION_CONFIG');
        }
    }
}
