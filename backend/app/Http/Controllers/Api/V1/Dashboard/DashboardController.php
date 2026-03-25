<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Provides the main dashboard metrics for the Driip back-office panel.
 *
 * Aggregates key business indicators — orders, revenue, fulfilment queues,
 * low-stock alerts, and new customer registrations — into a single response.
 */
class DashboardController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:dashboard.view', only: ['index']),
        ];
    }

    /**
     * Provide a summary of today's key business metrics for the panel dashboard.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'orders_today' => 0,
                    'revenue_today' => 0,
                    'orders_pending' => 0,
                    'orders_to_pack' => 0,
                    'orders_to_ship' => 0,
                    'low_stock_count' => 0,
                    'customers_today' => 0,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DASHBOARD_METRICS');
        }
    }
}
