<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Models\Order;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Payment reporting controller for dashboards and summaries.
 */
class PaymentReportController extends BaseApiController
{
    /**
     * Get payment summary report by date range.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'payment_method' => 'nullable|string|in:cod,bank_transfer,momo,zalopay,vnpay,credit_card,cash,loyalty_points',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'PAYMENT_SUMMARY');
        }

        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $paymentMethod = $request->input('payment_method');

            $query = Order::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

            if ($paymentMethod) {
                $query->where('payment_method', $paymentMethod);
            }

            $summary = [
                'total_orders' => (clone $query)->count(),
                'total_revenue' => (clone $query)->sum('total_after_tax'),
                'total_collected' => (clone $query)->sum('deposit_amount'),
                'by_payment_method' => $this->getMethodBreakdown($dateFrom, $dateTo),
                'by_payment_status' => $this->getStatusBreakdown($dateFrom, $dateTo, $paymentMethod),
                'cod_pending_collection' => (clone $query)
                    ->where('payment_method', 'cod')
                    ->where('status', 'delivered')
                    ->whereNull('cod_collected_at')
                    ->count(),
                'cod_discrepancies' => (clone $query)
                    ->where('payment_method', 'cod')
                    ->where('cod_reconciliation_status', 'disputed')
                    ->count(),
            ];

            return response()->json(['data' => $summary]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'PAYMENT_SUMMARY');
        }
    }

    /**
     * Get outstanding (unpaid) orders report.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function outstanding(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'payment_method' => 'nullable|string|in:cod,bank_transfer,momo,zalopay,vnpay,credit_card,cash,loyalty_points',
            'days_overdue' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'OUTSTANDING_PAYMENTS');
        }

        try {
            $query = Order::whereIn('payment_status', ['unpaid', 'partial'])
                ->whereRaw('(total_after_tax - COALESCE(deposit_amount, 0)) > 0');

            if ($request->input('payment_method')) {
                $query->where('payment_method', $request->input('payment_method'));
            }

            if ($request->input('days_overdue')) {
                $query->where('created_at', '<=', now()->subDays($request->input('days_overdue')));
            }

            $orders = $query->with('customer')
                ->orderBy('created_at', 'asc')
                ->paginate($request->input('per_page', 20));

            return OrderResource::collection($orders)->response();
        } catch (\Throwable $e) {
            return $this->serverError($e, 'OUTSTANDING_PAYMENTS');
        }
    }

    /**
     * Get COD orders pending collection.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function codPendingCollection(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'COD_PENDING');
        }

        try {
            $orders = Order::where('payment_method', 'cod')
                ->where('status', 'delivered')
                ->whereNull('cod_collected_at')
                ->with('customer')
                ->orderBy('delivered_at', 'asc')
                ->paginate($request->input('per_page', 20));

            return OrderResource::collection($orders)->response();
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COD_PENDING');
        }
    }

    /**
     * Get COD collection discrepancies.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function codDiscrepancies(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'COD_DISCREPANCIES');
        }

        try {
            $orders = Order::where('payment_method', 'cod')
                ->where('cod_reconciliation_status', 'disputed')
                ->whereNotNull('cod_discrepancy_amount')
                ->with('customer')
                ->orderBy('cod_collected_at', 'desc')
                ->paginate($request->input('per_page', 20));

            return OrderResource::collection($orders)->response();
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COD_DISCREPANCIES');
        }
    }

    /**
     * Get daily payment collections.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function daily(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'DAILY_PAYMENTS');
        }

        try {
            $date = $request->input('date');
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';

            // Hourly breakdown
            $hourly = DB::table('order_payments')
                ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count, SUM(amount) as total')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->groupByRaw('EXTRACT(HOUR FROM created_at)')
                ->orderBy('hour')
                ->get();

            // Method breakdown
            $byMethod = DB::table('order_payments')
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->groupBy('payment_method')
                ->get();

            // Type breakdown
            $byType = DB::table('order_payments')
                ->selectRaw('payment_type, COUNT(*) as count, SUM(amount) as total')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->groupBy('payment_type')
                ->get();

            return response()->json([
                'data' => [
                    'date' => $date,
                    'total_payments' => $byMethod->sum('count'),
                    'total_amount' => $byMethod->sum('total'),
                    'hourly_breakdown' => $hourly,
                    'by_method' => $byMethod,
                    'by_type' => $byType,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DAILY_PAYMENTS');
        }
    }

    /**
     * Get payment method breakdown.
     *
     * @param  string  $dateFrom
     * @param  string  $dateTo
     * @return list<array{method: string, count: int, total: int}>
     */
    private function getMethodBreakdown(string $dateFrom, string $dateTo): array
    {
        return DB::table('orders')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_after_tax) as total')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'count' => (int) $row->count,
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Get payment status breakdown.
     *
     * @param  string       $dateFrom
     * @param  string       $dateTo
     * @param  string|null  $paymentMethod
     * @return list<array{status: string, count: int, total: int}>
     */
    private function getStatusBreakdown(string $dateFrom, string $dateTo, ?string $paymentMethod): array
    {
        $query = DB::table('orders')
            ->selectRaw('payment_status, COUNT(*) as count, SUM(total_after_tax) as total')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        return $query->groupBy('payment_status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->payment_status,
                'count' => (int) $row->count,
                'total' => (int) $row->total,
            ])
            ->toArray();
    }
}
