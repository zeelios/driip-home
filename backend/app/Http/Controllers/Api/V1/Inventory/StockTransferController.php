<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Inventory\StockTransferResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage inter-warehouse stock transfers.
 *
 * Supports the full transfer lifecycle: creation → approval → dispatch → receive.
 * Inventory transactions (transfer_out / transfer_in) are created during the
 * dispatch and receive steps respectively, all within database transactions.
 */
class StockTransferController extends BaseApiController
{
    /**
     * List all stock transfers with optional filtering.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $transfers = QueryBuilder::for(StockTransfer::class)
                ->allowedFilters('status', 'from_warehouse_id', 'to_warehouse_id')
                ->allowedSorts('created_at', 'transfer_number')
                ->with(['fromWarehouse', 'toWarehouse'])
                ->paginate($request->integer('per_page', 20));

            return StockTransferResource::collection($transfers);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_TRANSFERS');
        }
    }

    /**
     * Create a new stock transfer request with its line items.
     *
     * @param  Request  $request
     * @return StockTransferResource|JsonResponse
     */
    public function store(Request $request): StockTransferResource|JsonResponse
    {
        try {
            $request->validate([
                'from_warehouse_id'              => ['required', 'uuid'],
                'to_warehouse_id'                => ['required', 'uuid', 'different:from_warehouse_id'],
                'reason'                         => ['nullable', 'string'],
                'notes'                          => ['nullable', 'string'],
                'items'                          => ['required', 'array', 'min:1'],
                'items.*.product_variant_id'     => ['required', 'uuid'],
                'items.*.quantity_requested'     => ['required', 'integer', 'min:1'],
            ]);

            $transfer = DB::transaction(function () use ($request) {
                /** @var StockTransfer $transfer */
                $transfer = StockTransfer::create([
                    'transfer_number'   => 'TRF-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                    'from_warehouse_id' => $request->input('from_warehouse_id'),
                    'to_warehouse_id'   => $request->input('to_warehouse_id'),
                    'status'            => 'draft',
                    'reason'            => $request->input('reason'),
                    'notes'             => $request->input('notes'),
                    'requested_by'      => $request->user()->id,
                ]);

                foreach ($request->input('items', []) as $item) {
                    StockTransferItem::create([
                        'stock_transfer_id'   => $transfer->id,
                        'product_variant_id'  => $item['product_variant_id'],
                        'quantity_requested'  => $item['quantity_requested'],
                        'quantity_dispatched' => 0,
                        'quantity_received'   => 0,
                    ]);
                }

                return $transfer;
            });

            return StockTransferResource::make(
                $transfer->load(['fromWarehouse', 'toWarehouse', 'items'])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_TRANSFER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_TRANSFER');
        }
    }

    /**
     * Show a single stock transfer with its items.
     *
     * @param  StockTransfer  $stockTransfer
     * @return StockTransferResource
     */
    public function show(StockTransfer $stockTransfer): StockTransferResource
    {
        return StockTransferResource::make(
            $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items'])
        );
    }

    /**
     * Update an existing stock transfer.
     *
     * Only draft transfers should be updated in normal workflow.
     *
     * @param  Request        $request
     * @param  StockTransfer  $stockTransfer
     * @return StockTransferResource|JsonResponse
     */
    public function update(Request $request, StockTransfer $stockTransfer): StockTransferResource|JsonResponse
    {
        try {
            $request->validate([
                'reason' => ['nullable', 'string'],
                'notes'  => ['nullable', 'string'],
            ]);

            $stockTransfer->update($request->only(['reason', 'notes']));

            return StockTransferResource::make(
                $stockTransfer->fresh()->load(['fromWarehouse', 'toWarehouse', 'items'])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_TRANSFER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_TRANSFER');
        }
    }

    /**
     * Soft-delete a stock transfer.
     *
     * @param  StockTransfer  $stockTransfer
     * @return JsonResponse
     */
    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $stockTransfer->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_TRANSFER');
        }
    }

    /**
     * Approve a stock transfer.
     *
     * Sets status to 'approved' and records the approver.
     *
     * @param  Request        $request
     * @param  StockTransfer  $stockTransfer
     * @return StockTransferResource|JsonResponse
     */
    public function approve(Request $request, StockTransfer $stockTransfer): StockTransferResource|JsonResponse
    {
        try {
            $stockTransfer->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
            ]);

            return StockTransferResource::make(
                $stockTransfer->fresh()->load(['fromWarehouse', 'toWarehouse', 'items'])
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'APPROVE_TRANSFER');
        }
    }

    /**
     * Dispatch an approved stock transfer.
     *
     * Creates transfer_out InventoryTransaction records for each item
     * in the source warehouse and marks the transfer as 'dispatched'.
     *
     * @param  Request        $request
     * @param  StockTransfer  $stockTransfer
     * @return StockTransferResource|JsonResponse
     */
    public function dispatch(Request $request, StockTransfer $stockTransfer): StockTransferResource|JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $stockTransfer) {
                $stockTransfer->loadMissing('items');

                foreach ($stockTransfer->items as $item) {
                    /** @var Inventory|null $inventory */
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $stockTransfer->from_warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) continue;

                    $qty    = $item->quantity_requested;
                    $before = $inventory->quantity_on_hand;

                    $inventory->quantity_on_hand  -= $qty;
                    $inventory->quantity_available = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                    $inventory->updated_at         = now();
                    $inventory->save();

                    $item->update(['quantity_dispatched' => $qty]);

                    InventoryTransaction::create([
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id'       => $stockTransfer->from_warehouse_id,
                        'type'               => 'transfer_out',
                        'quantity'           => $qty,
                        'quantity_before'    => $before,
                        'quantity_after'     => $inventory->quantity_on_hand,
                        'reference_type'     => 'stock_transfer',
                        'reference_id'       => $stockTransfer->id,
                        'created_by'         => $request->user()->id,
                        'created_at'         => now(),
                    ]);
                }

                $stockTransfer->update([
                    'status'        => 'dispatched',
                    'dispatched_at' => now(),
                ]);
            });

            return StockTransferResource::make(
                $stockTransfer->fresh()->load(['fromWarehouse', 'toWarehouse', 'items'])
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DISPATCH_TRANSFER');
        }
    }

    /**
     * Receive a dispatched stock transfer.
     *
     * Creates transfer_in InventoryTransaction records for each item
     * in the destination warehouse and marks the transfer as 'received'.
     *
     * @param  Request        $request
     * @param  StockTransfer  $stockTransfer
     * @return StockTransferResource|JsonResponse
     */
    public function receive(Request $request, StockTransfer $stockTransfer): StockTransferResource|JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $stockTransfer) {
                $stockTransfer->loadMissing('items');

                foreach ($stockTransfer->items as $item) {
                    $qty = $item->quantity_dispatched;

                    /** @var Inventory|null $inventory */
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $stockTransfer->to_warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        $inventory = Inventory::create([
                            'product_variant_id'  => $item->product_variant_id,
                            'warehouse_id'        => $stockTransfer->to_warehouse_id,
                            'quantity_on_hand'    => 0,
                            'quantity_reserved'   => 0,
                            'quantity_available'  => 0,
                            'quantity_incoming'   => 0,
                        ]);
                    }

                    $before = $inventory->quantity_on_hand;

                    $inventory->quantity_on_hand  += $qty;
                    $inventory->quantity_available = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                    $inventory->updated_at         = now();
                    $inventory->save();

                    $item->update(['quantity_received' => $qty]);

                    InventoryTransaction::create([
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id'       => $stockTransfer->to_warehouse_id,
                        'type'               => 'transfer_in',
                        'quantity'           => $qty,
                        'quantity_before'    => $before,
                        'quantity_after'     => $inventory->quantity_on_hand,
                        'reference_type'     => 'stock_transfer',
                        'reference_id'       => $stockTransfer->id,
                        'created_by'         => $request->user()->id,
                        'created_at'         => now(),
                    ]);
                }

                $stockTransfer->update([
                    'status'      => 'received',
                    'received_at' => now(),
                ]);
            });

            return StockTransferResource::make(
                $stockTransfer->fresh()->load(['fromWarehouse', 'toWarehouse', 'items'])
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'RECEIVE_TRANSFER');
        }
    }
}
