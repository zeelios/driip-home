<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Warehouse;

use App\Domain\Warehouse\Models\Warehouse;
use App\Domain\Warehouse\Models\WarehouseStaff;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Warehouse\CreateWarehouseRequest;
use App\Http\Resources\Inventory\InventoryResource;
use App\Http\Resources\Warehouse\WarehouseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage warehouses and their staff assignments.
 *
 * Handles CRUD for warehouses, exposes a per-warehouse paginated inventory
 * view, and provides an endpoint to assign staff to a warehouse.
 */
class WarehouseController extends BaseApiController
{
    /**
     * List all warehouses with optional filtering.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $warehouses = QueryBuilder::for(Warehouse::class)
                ->allowedFilters('name', 'code', 'type', 'is_active', 'province')
                ->allowedSorts('name', 'code', 'created_at')
                ->with(['manager'])
                ->paginate($request->integer('per_page', 20));

            return WarehouseResource::collection($warehouses);
        } catch (\Throwable $e) {
            return WarehouseResource::collection(
                (new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20))
            );
        }
    }

    /**
     * Create a new warehouse.
     *
     * @param  CreateWarehouseRequest  $request
     * @return WarehouseResource|JsonResponse
     */
    public function store(CreateWarehouseRequest $request): WarehouseResource|JsonResponse
    {
        try {
            $warehouse = Warehouse::create($request->validated());

            return WarehouseResource::make($warehouse->load('manager'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_WAREHOUSE');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_WAREHOUSE');
        }
    }

    /**
     * Show a single warehouse.
     *
     * @param  Warehouse  $warehouse
     * @return WarehouseResource
     */
    public function show(Warehouse $warehouse): WarehouseResource
    {
        return WarehouseResource::make($warehouse->load(['manager', 'staffAssignments']));
    }

    /**
     * Update an existing warehouse.
     *
     * @param  Request    $request
     * @param  Warehouse  $warehouse
     * @return WarehouseResource|JsonResponse
     */
    public function update(Request $request, Warehouse $warehouse): WarehouseResource|JsonResponse
    {
        try {
            $request->validate([
                'code'       => ['sometimes', 'string', 'max:50', 'unique:warehouses,code,' . $warehouse->id],
                'name'       => ['sometimes', 'string', 'max:255'],
                'type'       => ['sometimes', 'string', 'in:main,satellite,virtual,consignment'],
                'address'    => ['nullable', 'string'],
                'province'   => ['nullable', 'string', 'max:100'],
                'district'   => ['nullable', 'string', 'max:100'],
                'phone'      => ['nullable', 'string', 'max:20'],
                'manager_id' => ['nullable', 'uuid'],
                'is_active'  => ['nullable', 'boolean'],
                'notes'      => ['nullable', 'string'],
            ]);

            $warehouse->update($request->all());

            return WarehouseResource::make($warehouse->fresh()->load('manager'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_WAREHOUSE');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_WAREHOUSE');
        }
    }

    /**
     * Delete a warehouse.
     *
     * @param  Warehouse  $warehouse
     * @return JsonResponse
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        try {
            $warehouse->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_WAREHOUSE');
        }
    }

    /**
     * List the inventory held in a specific warehouse (paginated).
     *
     * @param  Request    $request
     * @param  Warehouse  $warehouse
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function inventory(Request $request, Warehouse $warehouse): AnonymousResourceCollection|JsonResponse
    {
        try {
            $inventory = QueryBuilder::for(
                $warehouse->inventory()->with(['variant.product'])->getQuery()
            )
                ->allowedFilters('product_variant_id')
                ->paginate($request->integer('per_page', 20));

            return InventoryResource::collection($inventory);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_WAREHOUSE_INVENTORY');
        }
    }

    /**
     * Assign a staff member to a warehouse with a specified role.
     *
     * Body: { user_id: uuid, role: string }.
     *
     * @param  Request    $request
     * @param  Warehouse  $warehouse
     * @return JsonResponse
     */
    public function assignStaff(Request $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => ['required', 'uuid', 'exists:users,id'],
                'role'    => ['required', 'string', 'max:100'],
            ]);

            $assignment = WarehouseStaff::create([
                'warehouse_id' => $warehouse->id,
                'user_id'      => $request->input('user_id'),
                'role'         => $request->input('role'),
                'assigned_at'  => now()->toDateString(),
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'          => $assignment->id,
                    'warehouse_id' => $assignment->warehouse_id,
                    'user_id'     => $assignment->user_id,
                    'role'        => $assignment->role,
                    'assigned_at' => $assignment->assigned_at?->toDateString(),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'ASSIGN_WAREHOUSE_STAFF');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'ASSIGN_WAREHOUSE_STAFF');
        }
    }
}
