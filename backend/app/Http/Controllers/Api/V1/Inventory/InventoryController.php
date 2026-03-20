<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Actions\AdjustInventoryAction;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Inventory\AdjustInventoryRequest;
use App\Http\Resources\Inventory\InventoryResource;
use App\Http\Resources\Inventory\InventoryTransactionResource;
use App\Jobs\ExportInventoryJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage product inventory levels and movements across warehouses.
 *
 * Exposes endpoints for listing inventory, viewing stock per variant,
 * applying manual adjustments, browsing transaction history, and
 * dispatching background export jobs.
 */
class InventoryController extends BaseApiController
{
    /**
     * @param  AdjustInventoryAction  $adjustAction  Action that applies inventory adjustments.
     */
    public function __construct(
        private readonly AdjustInventoryAction $adjustAction,
    ) {}

    /**
     * List all inventory records with optional filtering.
     *
     * Supports filtering by warehouse_id and variant_id.
     * Eagerly loads variant.product and warehouse for each record.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $inventory = QueryBuilder::for(Inventory::class)
                ->allowedFilters('warehouse_id', 'product_variant_id')
                ->with(['variant.product', 'warehouse'])
                ->paginate($request->integer('per_page', 20));

            return InventoryResource::collection($inventory);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_INVENTORY');
        }
    }

    /**
     * Show all inventory records for a specific product variant across all warehouses.
     *
     * @param  string  $variant  The product variant UUID.
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function show(string $variant): AnonymousResourceCollection|JsonResponse
    {
        try {
            $records = Inventory::where('product_variant_id', $variant)
                ->with(['variant.product', 'warehouse'])
                ->get();

            return InventoryResource::collection($records);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_INVENTORY');
        }
    }

    /**
     * Apply a manual stock adjustment for a variant in a warehouse.
     *
     * Positive quantity adds stock; negative quantity removes it.
     * Returns 409 if the adjustment would result in negative on-hand stock.
     *
     * @param  AdjustInventoryRequest  $request
     * @return JsonResponse
     */
    public function adjust(AdjustInventoryRequest $request): JsonResponse
    {
        try {
            $transaction = $this->adjustAction->execute(
                variantId:     $request->input('variant_id'),
                warehouseId:   $request->input('warehouse_id'),
                quantityDelta: (int) $request->input('quantity'),
                reason:        $request->input('reason'),
                createdBy:     $request->user()->id,
            );

            return response()->json([
                'success' => true,
                'data'    => InventoryTransactionResource::make($transaction),
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'ADJUST_INVENTORY');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'ADJUST_INVENTORY');
        }
    }

    /**
     * List inventory transactions (stock movements) with optional filtering.
     *
     * Supports filtering by warehouse_id, product_variant_id, type,
     * and date range (created_after, created_before).
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function movements(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = QueryBuilder::for(InventoryTransaction::class)
                ->allowedFilters(
                    'warehouse_id',
                    'product_variant_id',
                    'type',
                    AllowedFilter::callback('created_after', function ($query, $value) {
                        $query->where('created_at', '>=', $value);
                    }),
                    AllowedFilter::callback('created_before', function ($query, $value) {
                        $query->where('created_at', '<=', $value);
                    }),
                )
                ->allowedSorts('created_at', 'type')
                ->with(['variant', 'warehouse'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->integer('per_page', 30));

            return InventoryTransactionResource::collection($query);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_INVENTORY_MOVEMENTS');
        }
    }

    /**
     * Dispatch a background job to export inventory data to a file.
     *
     * Returns immediately with a queued confirmation. The export job will
     * produce a downloadable file and can notify the user when complete.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            ExportInventoryJob::dispatch($request->user()->id);

            return response()->json([
                'success' => true,
                'queued'  => true,
                'message' => 'Inventory export has been queued.',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'EXPORT_INVENTORY');
        }
    }
}
