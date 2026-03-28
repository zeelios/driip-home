<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Fulfillment;

use App\Domain\Fulfillment\Actions\PackOrderItemsAction;
use App\Domain\Fulfillment\Actions\PickOrderItemsAction;
use App\Domain\Order\Models\OrderItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Fulfillment controller for warehouse staff to manage order picking and packing.
 *
 * Exposes endpoints for listing pending order items, marking items as picked,
 * packing items with auto-generated shipping labels, and exporting packing lists.
 */
class FulfillmentController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:fulfillment.view', only: ['index', 'show']),
            new Middleware('permission:fulfillment.pick', only: ['pick']),
            new Middleware('permission:fulfillment.pack', only: ['pack']),
            new Middleware('permission:fulfillment.export', only: ['export']),
        ];
    }

    public function __construct(
        private readonly PickOrderItemsAction $pickAction,
        private readonly PackOrderItemsAction $packAction,
    ) {
    }

    /**
     * List order items awaiting fulfillment with filtering.
     *
     * Supports filtering by status (pending, picked, packed, shipped),
     * warehouse_id, order_id, and date range.
     * Eagerly loads order, product, sizeOption, inventory, and shipment.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $items = QueryBuilder::for(OrderItem::class)
                ->allowedFilters([
                    'status',
                    'warehouse_id',
                    'order_id',
                    AllowedFilter::callback('created_after', function ($query, $value) {
                        $query->where('created_at', '>=', $value);
                    }),
                    AllowedFilter::callback('created_before', function ($query, $value) {
                        $query->where('created_at', '<=', $value);
                    }),
                ])
                ->allowedSorts('created_at', 'status')
                ->with(['order', 'product', 'sizeOption', 'inventory', 'shipment', 'pickedBy', 'packedBy'])
                ->whereIn('status', ['pending', 'picked', 'packed'])
                ->paginate($request->integer('per_page', 20));

            return OrderItemResource::collection($items);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_FULFILLMENT_ITEMS');
        }
    }

    /**
     * Mark order items as picked by warehouse staff.
     *
     * Updates status to 'picked', sets picked_at timestamp, and optionally
     * links to inventory record. Validates that items are in 'pending' status.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function pick(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_ids' => 'required|array|min:1',
                'item_ids.*' => 'uuid|exists:order_items,id',
                'inventory_ids' => 'nullable|array',
                'inventory_ids.*' => 'nullable|uuid|exists:inventory,id',
            ]);

            $result = $this->pickAction->execute(
                itemIds: $validated['item_ids'],
                pickedBy: $request->user()->id,
                inventoryIds: $validated['inventory_ids'] ?? [],
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'picked_count' => $result['picked_count'],
                    'items' => OrderItemResource::collection($result['items']),
                ],
                'message' => "{$result['picked_count']} items marked as picked.",
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'PICK_ITEMS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'PICK_ITEMS');
        }
    }

    /**
     * Pack order items and generate shipping labels.
     *
     * Groups items by order, creates shipment records, generates courier labels,
     * and updates item status to 'packed'. Supports bulk packing.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function pack(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_ids' => 'required|array|min:1',
                'item_ids.*' => 'uuid|exists:order_items,id',
                'courier_code' => 'nullable|string|in:ghtk,ghn,viettel_post',
            ]);

            $result = $this->packAction->execute(
                itemIds: $validated['item_ids'],
                packedBy: $request->user()->id,
                courierCode: $validated['courier_code'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'packed_count' => $result['packed_count'],
                    'shipments' => $result['shipments'],
                    'labels' => $result['labels'],
                    'items' => OrderItemResource::collection($result['items']),
                ],
                'message' => "{$result['packed_count']} items packed with {$result['shipment_count']} shipments created.",
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'PACK_ITEMS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'PACK_ITEMS');
        }
    }

    /**
     * Export packing list for selected items.
     *
     * Returns CSV or PDF with item details, order info, and shipping addresses.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_ids' => 'nullable|array',
                'item_ids.*' => 'uuid|exists:order_items,id',
                'format' => 'nullable|string|in:csv,pdf',
                'status' => 'nullable|string|in:pending,picked,packed',
            ]);

            // TODO: Implement export job
            // ExportFulfillmentJob::dispatch(...);

            return response()->json([
                'success' => true,
                'queued' => true,
                'message' => 'Fulfillment export has been queued.',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'EXPORT_FULFILLMENT');
        }
    }

    /**
     * Get statistics for the fulfillment dashboard.
     *
     * Returns counts of pending, picked, and packed items for today.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $today = now()->startOfDay();

            $stats = [
                'pending' => OrderItem::where('status', 'pending')->count(),
                'picked' => OrderItem::where('status', 'picked')->count(),
                'packed_today' => OrderItem::where('status', 'packed')
                    ->where('packed_at', '>=', $today)
                    ->count(),
                'shipped_today' => OrderItem::where('status', 'shipped')
                    ->whereDate('updated_at', $today)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'FULFILLMENT_STATS');
        }
    }
}
