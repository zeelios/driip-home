<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Order\Models\OrderItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller for managing purchase requests and low stock alerts.
 *
 * Combines low inventory levels with unfulfillable order items to
 * generate purchase recommendations.
 * NOTE: Supplier grouping is disabled until supplier relationship is implemented.
 */
class PurchaseRequestController extends BaseApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchase_request.view', only: ['index', 'lowStock', 'unfulfillable', 'bySupplier']),
            new Middleware('permission:purchase_request.create', only: ['createPurchaseOrders']),
        ];
    }

    /**
     * Get summary of purchase request data.
     */
    public function index(): JsonResponse
    {
        try {
            $lowStockCount = Inventory::whereRaw('quantity_available < reorder_point')->count();
            $unfulfillableCount = OrderItem::where('status', 'pending')
                ->whereDoesntHave('inventory', function ($q) {
                    $q->where('quantity_available', '>', 0);
                })
                ->count();

            // Calculate estimated cost
            $lowStockItems = Inventory::whereRaw('quantity_available < reorder_point')
                ->with('product')
                ->get();

            $estimatedCost = $lowStockItems->sum(function ($inv) {
                $needed = ($inv->reorder_point ?? 10) - $inv->quantity_available;
                $cost = $inv->product?->cost_price ?? 0;
                return $needed * $cost;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock_count' => $lowStockCount,
                    'unfulfillable_count' => $unfulfillableCount,
                    'total_items_needing_purchase' => $lowStockCount + $unfulfillableCount,
                    'estimated_cost' => $estimatedCost,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'PURCHASE_REQUEST_SUMMARY');
        }
    }

    /**
     * List low stock inventory items.
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $items = Inventory::whereRaw('quantity_available < reorder_point')
                ->orWhere('quantity_available', '<=', 0)
                ->with(['product', 'warehouse'])
                ->paginate($request->integer('per_page', 20));

            $data = $items->map(function ($inv) {
                $product = $inv->product;

                return [
                    'id' => $inv->id,
                    'product_id' => $inv->product_id,
                    'sku' => $product?->sku,
                    'product_name' => $product?->name,
                    'warehouse_id' => $inv->warehouse_id,
                    'warehouse_name' => $inv->warehouse?->name,
                    'quantity_available' => $inv->quantity_available,
                    'quantity_on_hand' => $inv->quantity_on_hand,
                    'reorder_point' => $inv->reorder_point ?? 10,
                    'suggested_quantity' => max(($inv->reorder_point ?? 10) * 2 - $inv->quantity_available, 10),
                    'unit_cost' => $product?->cost_price ?? 0,
                    'supplier' => null, // TODO: Enable when supplier relationship is implemented
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'total' => $items->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LOW_STOCK_ITEMS');
        }
    }

    /**
     * List unfulfillable order items (pending but no stock).
     */
    public function unfulfillable(Request $request): JsonResponse
    {
        try {
            $items = OrderItem::where('status', 'pending')
                ->whereDoesntHave('inventory', function ($q) {
                    $q->where('quantity_available', '>', 0);
                })
                ->with(['order', 'product'])
                ->paginate($request->integer('per_page', 20));

            $data = $items->map(function ($item) {
                $product = $item->product;

                return [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'order_number' => $item->order?->order_number,
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'product_name' => $item->name,
                    'size_display' => $item->sizeOption?->display_name ?? $item->sizeOption?->code,
                    'quantity_needed' => 1,
                    'order_date' => $item->order?->created_at,
                    'customer_name' => $item->order?->shipping_name,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $product?->cost_price ?? 0,
                    'supplier' => null, // TODO: Enable when supplier relationship is implemented
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'total' => $items->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UNFULFILLABLE_ITEMS');
        }
    }

    /**
     * Get details of selected purchase request items by IDs.
     * Items can be from either low_stock (inventory) or unfulfillable (order_items).
     */
    public function getSelectedItems(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'uuid',
                'type' => 'required|in:low_stock,unfulfillable',
            ]);

            $ids = $validated['ids'];
            $type = $validated['type'];

            if ($type === 'low_stock') {
                $items = Inventory::whereIn('id', $ids)
                    ->with(['product', 'warehouse'])
                    ->get();

                $data = $items->map(function ($inv) {
                    $product = $inv->product;
                    $needed = max(($inv->reorder_point ?? 10) * 2 - $inv->quantity_available, 10);

                    return [
                        'id' => $inv->id,
                        'type' => 'low_stock',
                        'product_id' => $inv->product_id,
                        'sku' => $product?->sku,
                        'product_name' => $product?->name,
                        'size_option_id' => null,
                        'size_display' => null,
                        'color' => null,
                        'warehouse_id' => $inv->warehouse_id,
                        'warehouse_name' => $inv->warehouse?->name,
                        'quantity_needed' => $needed,
                        'quantity_available' => $inv->quantity_available,
                        'unit_cost' => $product?->cost_price ?? 0,
                        'supplier' => null,
                    ];
                });
            } else {
                $items = OrderItem::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->with(['order', 'product', 'sizeOption'])
                    ->get();

                $data = $items->map(function ($item) {
                    $product = $item->product;

                    return [
                        'id' => $item->id,
                        'type' => 'unfulfillable',
                        'product_id' => $item->product_id,
                        'sku' => $item->sku,
                        'product_name' => $item->name,
                        'size_option_id' => $item->size_option_id,
                        'size_display' => $item->sizeOption?->display_name ?? $item->sizeOption?->code,
                        'color' => $item->color,
                        'warehouse_id' => null,
                        'warehouse_name' => null,
                        'order_id' => $item->order_id,
                        'order_number' => $item->order?->order_number,
                        'quantity_needed' => 1,
                        'unit_cost' => $product?->cost_price ?? 0,
                        'supplier' => null,
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'GET_SELECTED_ITEMS');
        }
    }

    /**
     * Group items by supplier for PO creation.
     * NOTE: Disabled until supplier relationship is implemented.
     */
    public function bySupplier(): JsonResponse
    {
        // TODO: Enable when supplier relationship is implemented
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Supplier grouping will be available when supplier feature is implemented.',
        ]);
    }

    /**
     * Create draft purchase orders from selected items.
     * NOTE: Disabled until supplier relationship is implemented.
     */
    public function createPurchaseOrders(Request $request): JsonResponse
    {
        // TODO: Enable when supplier relationship is implemented
        return response()->json([
            'success' => true,
            'message' => 'Purchase order creation will be available when supplier feature is implemented.',
            'data' => [
                'created_count' => 0,
                'purchase_orders' => [],
            ],
        ]);
    }
}
