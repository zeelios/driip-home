<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Domain\Inventory\Actions\ReceivePurchaseOrderAction;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Inventory\CreatePurchaseOrderRequest;
use App\Http\Requests\Inventory\ReceivePurchaseOrderRequest;
use App\Http\Resources\Inventory\PurchaseOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage purchase orders placed with suppliers.
 *
 * Handles full CRUD, approval workflow, and goods-receipt recording.
 * The receive endpoint delegates to ReceivePurchaseOrderAction which
 * updates inventory quantities within a single database transaction.
 */
class PurchaseOrderController extends BaseApiController
{
    /**
     * @param  ReceivePurchaseOrderAction  $receiveAction  Action that processes goods receipt.
     */
    public function __construct(
        private readonly ReceivePurchaseOrderAction $receiveAction,
    ) {}

    /**
     * List all purchase orders with optional filtering and sorting.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $orders = QueryBuilder::for(PurchaseOrder::class)
                ->allowedFilters(['status', 'supplier_id', 'warehouse_id'])
                ->allowedSorts(['created_at', 'expected_arrival_at', 'po_number'])
                ->with(['supplier', 'warehouse'])
                ->paginate($request->integer('per_page', 20));

            return PurchaseOrderResource::collection($orders);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_POS');
        }
    }

    /**
     * Create a new purchase order with its line items.
     *
     * Generates a PO number, creates the header record, then creates
     * PurchaseOrderItem rows for each item in the request.
     *
     * @param  CreatePurchaseOrderRequest  $request
     * @return PurchaseOrderResource|JsonResponse
     */
    public function store(CreatePurchaseOrderRequest $request): PurchaseOrderResource|JsonResponse
    {
        try {
            $po = PurchaseOrder::create([
                'po_number'           => 'PO-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'supplier_id'         => $request->input('supplier_id'),
                'warehouse_id'        => $request->input('warehouse_id'),
                'status'              => 'draft',
                'expected_arrival_at' => $request->input('expected_arrival_at'),
                'notes'               => $request->input('notes'),
                'shipping_cost'       => 0,
                'other_costs'         => 0,
                'total_cost'          => 0,
                'created_by'          => $request->user()->id,
            ]);

            foreach ($request->input('items', []) as $item) {
                /** @var \App\Domain\Product\Models\ProductVariant|null $variant */
                $variant = \App\Domain\Product\Models\ProductVariant::find($item['product_variant_id']);

                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'sku'                => $variant?->sku ?? '',
                    'quantity_ordered'   => $item['quantity_ordered'],
                    'quantity_received'  => 0,
                    'unit_cost'          => $item['unit_cost'],
                    'total_cost'         => $item['unit_cost'] * $item['quantity_ordered'],
                ]);
            }

            return PurchaseOrderResource::make($po->load(['supplier', 'warehouse', 'items']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_PO');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_PO');
        }
    }

    /**
     * Show a single purchase order with its items and receipts.
     *
     * @param  PurchaseOrder  $purchaseOrder
     * @return PurchaseOrderResource
     */
    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return PurchaseOrderResource::make(
            $purchaseOrder->load(['supplier', 'warehouse', 'items', 'receipts'])
        );
    }

    /**
     * Update an existing purchase order.
     *
     * Only draft/pending purchase orders should be updated in normal flow.
     *
     * @param  Request        $request
     * @param  PurchaseOrder  $purchaseOrder
     * @return PurchaseOrderResource|JsonResponse
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        try {
            $request->validate([
                'expected_arrival_at' => ['nullable', 'date'],
                'notes'               => ['nullable', 'string'],
                'shipping_cost'       => ['nullable', 'integer', 'min:0'],
                'other_costs'         => ['nullable', 'integer', 'min:0'],
            ]);

            $purchaseOrder->update($request->only([
                'expected_arrival_at', 'notes', 'shipping_cost', 'other_costs',
            ]));

            return PurchaseOrderResource::make(
                $purchaseOrder->fresh()->load(['supplier', 'warehouse', 'items'])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_PO');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_PO');
        }
    }

    /**
     * Soft-delete a purchase order.
     *
     * @param  PurchaseOrder  $purchaseOrder
     * @return JsonResponse
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_PO');
        }
    }

    /**
     * Approve a purchase order.
     *
     * Sets the status to 'confirmed', records the approver and timestamp.
     *
     * @param  Request        $request
     * @param  PurchaseOrder  $purchaseOrder
     * @return PurchaseOrderResource|JsonResponse
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource|JsonResponse
    {
        try {
            $purchaseOrder->update([
                'status'      => 'confirmed',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return PurchaseOrderResource::make(
                $purchaseOrder->fresh()->load(['supplier', 'warehouse', 'items'])
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'APPROVE_PO');
        }
    }

    /**
     * Record goods receipt against a purchase order.
     *
     * Delegates the full inventory update logic to ReceivePurchaseOrderAction.
     *
     * @param  ReceivePurchaseOrderRequest  $request
     * @param  PurchaseOrder               $purchaseOrder
     * @return JsonResponse
     */
    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $receipt = $this->receiveAction->execute($purchaseOrder, $request->validated());

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'             => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                    'received_at'    => $receipt->received_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'RECEIVE_PO');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'RECEIVE_PO');
        }
    }
}
