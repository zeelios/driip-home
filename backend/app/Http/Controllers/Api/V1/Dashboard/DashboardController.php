<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides the main dashboard metrics for the Driip back-office panel.
 *
 * Aggregates key business indicators — orders, revenue, fulfilment queues,
 * low-stock alerts, and new customer registrations — into a single response.
 */
class DashboardController extends BaseApiController
{
    /**
     * Provide a summary of today's key business metrics for the panel dashboard.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'orders_today'    => \App\Domain\Order\Models\Order::whereDate('created_at', today())->count(),
                'revenue_today'   => \App\Domain\Order\Models\Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('total_after_tax'),
                'orders_pending'  => \App\Domain\Order\Models\Order::where('status', 'pending')->count(),
                'orders_to_pack'  => \App\Domain\Order\Models\Order::whereIn('status', ['confirmed', 'processing'])->count(),
                'orders_to_ship'  => \App\Domain\Order\Models\Order::where('status', 'packed')->count(),
                'low_stock_count' => \App\Domain\Inventory\Models\Inventory::whereColumn('quantity_available', '<=', 'reorder_point')->whereNotNull('reorder_point')->count(),
                'customers_today' => \App\Domain\Customer\Models\Customer::whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
