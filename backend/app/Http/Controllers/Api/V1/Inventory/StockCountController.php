<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\StockCount;
use App\Domain\Inventory\Models\StockCountItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Inventory\StockCountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage stock count (physical inventory) tasks.
 *
 * Supports creating count tasks (which auto-populate items from warehouse inventory),
 * recording individual item counts, completing a count, and approving it —
 * which creates count_correction inventory transactions for all variances.
 */
class StockCountController extends BaseApiController
{
    /**
     * List all stock count tasks with optional filtering.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $counts = QueryBuilder::for(StockCount::class)
                ->allowedFilters('status', 'warehouse_id', 'type')
                ->allowedSorts('created_at', 'scheduled_at', 'count_number')
                ->with(['warehouse'])
                ->paginate($request->integer('per_page', 20));

            return StockCountResource::collection($counts);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_STOCK_COUNTS');
        }
    }

    /**
     * Create a new stock count task for a warehouse.
     *
     * Auto-populates count items with all variants currently in the warehouse,
     * setting quantity_expected from each inventory record's on-hand quantity.
     *
     * @param  Request  $request
     * @return StockCountResource|JsonResponse
     */
    public function store(Request $request): StockCountResource|JsonResponse
    {
        try {
            $request->validate([
                'warehouse_id' => ['required', 'uuid'],
                'type'         => ['required', 'string', 'in:full,partial,cycle_count,spot_check'],
                'scheduled_at' => ['nullable', 'date'],
                'notes'        => ['nullable', 'string'],
            ]);

            $count = DB::transaction(function () use ($request) {
                /** @var StockCount $count */
                $count = StockCount::create([
                    'count_number' => 'CNT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                    'warehouse_id' => $request->input('warehouse_id'),
                    'type'         => $request->input('type'),
                    'status'       => 'draft',
                    'scheduled_at' => $request->input('scheduled_at'),
                    'notes'        => $request->input('notes'),
                    'created_by'   => $request->user()->id,
                ]);

                // Auto-populate items from current warehouse inventory
                $inventoryItems = Inventory::where('warehouse_id', $request->input('warehouse_id'))->get();

                foreach ($inventoryItems as $inv) {
                    StockCountItem::create([
                        'stock_count_id'     => $count->id,
                        'product_variant_id' => $inv->product_variant_id,
                        'quantity_expected'  => $inv->quantity_on_hand,
                    ]);
                }

                return $count;
            });

            return StockCountResource::make($count->load(['warehouse', 'items']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_STOCK_COUNT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_STOCK_COUNT');
        }
    }

    /**
     * Show a single stock count task with its items.
     *
     * @param  StockCount  $stockCount
     * @return StockCountResource
     */
    public function show(StockCount $stockCount): StockCountResource
    {
        return StockCountResource::make($stockCount->load(['warehouse', 'items']));
    }

    /**
     * Update a stock count task (notes, scheduled_at).
     *
     * @param  Request     $request
     * @param  StockCount  $stockCount
     * @return StockCountResource|JsonResponse
     */
    public function update(Request $request, StockCount $stockCount): StockCountResource|JsonResponse
    {
        try {
            $request->validate([
                'scheduled_at' => ['nullable', 'date'],
                'notes'        => ['nullable', 'string'],
            ]);

            $stockCount->update($request->only(['scheduled_at', 'notes']));

            return StockCountResource::make($stockCount->fresh()->load(['warehouse', 'items']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_STOCK_COUNT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_STOCK_COUNT');
        }
    }

    /**
     * Record the physically counted quantity for a single stock count item.
     *
     * Body: { quantity_counted: int, notes?: string }.
     * Sets variance = quantity_counted − quantity_expected.
     *
     * @param  Request        $request
     * @param  StockCount     $stockCount
     * @param  StockCountItem $item
     * @return JsonResponse
     */
    public function countItem(Request $request, StockCount $stockCount, StockCountItem $item): JsonResponse
    {
        try {
            $request->validate([
                'quantity_counted' => ['required', 'integer', 'min:0'],
                'notes'            => ['nullable', 'string'],
            ]);

            $quantityCounted = (int) $request->input('quantity_counted');
            $variance        = $quantityCounted - $item->quantity_expected;

            $item->update([
                'quantity_counted' => $quantityCounted,
                'variance'         => $variance,
                'notes'            => $request->input('notes'),
                'counted_by'       => $request->user()->id,
                'counted_at'       => now(),
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'               => $item->id,
                    'quantity_expected' => $item->quantity_expected,
                    'quantity_counted'  => $item->quantity_counted,
                    'variance'          => $item->variance,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'COUNT_ITEM');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COUNT_ITEM');
        }
    }

    /**
     * Mark a stock count as complete.
     *
     * Calculates total variance quantities and values across all items.
     * The count must be in 'in_progress' or 'pending' status to be completed.
     *
     * @param  Request     $request
     * @param  StockCount  $stockCount
     * @return StockCountResource|JsonResponse
     */
    public function complete(Request $request, StockCount $stockCount): StockCountResource|JsonResponse
    {
        try {
            $stockCount->loadMissing('items');

            $totalVarianceQty   = $stockCount->items->sum('variance');
            $totalVarianceValue = 0;

            // Calculate monetary variance using variant cost price if available
            foreach ($stockCount->items as $item) {
                if ($item->variance !== null && $item->variance !== 0) {
                    /** @var \App\Domain\Product\Models\ProductVariant|null $variant */
                    $variant             = \App\Domain\Product\Models\ProductVariant::find($item->product_variant_id);
                    $costPrice           = $variant?->cost_price ?? 0;
                    $varValue            = ($item->variance ?? 0) * $costPrice;
                    $totalVarianceValue += $varValue;

                    $item->update(['variance_value' => $varValue]);
                }
            }

            $stockCount->update([
                'status'               => 'completed',
                'completed_at'         => now(),
                'total_variance_qty'   => $totalVarianceQty,
                'total_variance_value' => $totalVarianceValue,
            ]);

            return StockCountResource::make($stockCount->fresh()->load(['warehouse', 'items']));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COMPLETE_STOCK_COUNT');
        }
    }

    /**
     * Approve a completed stock count.
     *
     * Creates count_correction InventoryTransaction records for all items
     * with a non-zero variance, then updates inventory on-hand quantities.
     * The count transitions to 'approved' status.
     *
     * @param  Request     $request
     * @param  StockCount  $stockCount
     * @return StockCountResource|JsonResponse
     */
    public function approve(Request $request, StockCount $stockCount): StockCountResource|JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $stockCount) {
                $stockCount->loadMissing('items');

                foreach ($stockCount->items as $item) {
                    if (!$item->variance || $item->quantity_counted === null) {
                        continue;
                    }

                    /** @var Inventory|null $inventory */
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $stockCount->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) continue;

                    $before = $inventory->quantity_on_hand;

                    $inventory->quantity_on_hand   = $item->quantity_counted;
                    $inventory->quantity_available = $item->quantity_counted - $inventory->quantity_reserved;
                    $inventory->last_counted_at    = now();
                    $inventory->updated_at         = now();
                    $inventory->save();

                    InventoryTransaction::create([
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id'       => $stockCount->warehouse_id,
                        'type'               => 'count_correction',
                        'quantity'           => $item->variance,
                        'quantity_before'    => $before,
                        'quantity_after'     => $item->quantity_counted,
                        'reference_type'     => 'stock_count',
                        'reference_id'       => $stockCount->id,
                        'created_by'         => $request->user()->id,
                        'created_at'         => now(),
                    ]);
                }

                $stockCount->update([
                    'status'      => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
            });

            return StockCountResource::make($stockCount->fresh()->load(['warehouse', 'items']));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'APPROVE_STOCK_COUNT');
        }
    }
}
