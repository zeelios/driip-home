<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\SaleEvent;

use App\Domain\SaleEvent\Actions\ActivateSaleEventAction;
use App\Domain\SaleEvent\Actions\EndSaleEventAction;
use App\Domain\SaleEvent\Models\SaleEvent;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\SaleEvent\SaleEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for sale event management.
 *
 * Provides standard CRUD plus dedicated activate and end endpoints that
 * delegate to their respective actions to atomically transition the event
 * status and update variant price overrides.
 */
class SaleEventController extends BaseApiController
{
    /**
     * List all sale events with optional filtering and pagination.
     *
     * Allowed filters: type, status, is_public.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $events = QueryBuilder::for(SaleEvent::class)
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('is_public'),
                )
                ->allowedSorts('name', 'starts_at', 'ends_at', 'created_at', 'status')
                ->paginate(20);

            return SaleEventResource::collection($events);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_SALE_EVENTS');
        }
    }

    /**
     * Create a new sale event.
     *
     * @param  Request  $request
     * @return SaleEventResource|JsonResponse
     */
    public function store(Request $request): SaleEventResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'              => ['required', 'string', 'max:255'],
                'slug'              => ['required', 'string', 'max:255', 'unique:sale_events,slug'],
                'description'       => ['nullable', 'string'],
                'type'              => ['required', 'in:flash_sale,drop_launch,clearance,bundle'],
                'status'            => ['nullable', 'in:draft,scheduled,active,ended,cancelled'],
                'starts_at'         => ['required', 'date'],
                'ends_at'           => ['nullable', 'date', 'after:starts_at'],
                'max_orders_total'  => ['nullable', 'integer', 'min:1'],
                'is_public'         => ['nullable', 'boolean'],
                'banner_url'        => ['nullable', 'string', 'max:500'],
                'items'             => ['nullable', 'array'],
                'items.*.product_variant_id'   => ['required_with:items', 'uuid', 'exists:product_variants,id'],
                'items.*.sale_price'           => ['required_with:items', 'integer', 'min:0'],
                'items.*.compare_price'        => ['nullable', 'integer', 'min:0'],
                'items.*.max_qty_per_customer' => ['nullable', 'integer', 'min:1'],
                'items.*.max_qty_total'        => ['nullable', 'integer', 'min:1'],
            ]);

            $itemsData = $validated['items'] ?? [];
            unset($validated['items']);

            $validated['created_by'] = $request->user()?->id;

            $event = SaleEvent::create($validated);

            if (!empty($itemsData)) {
                foreach ($itemsData as $itemRow) {
                    $event->items()->create($itemRow);
                }
            }

            return new SaleEventResource($event->load('items.variant'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_SALE_EVENT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_SALE_EVENT');
        }
    }

    /**
     * Retrieve a single sale event including its items and their variants.
     *
     * @param  SaleEvent  $saleEvent
     * @return SaleEventResource|JsonResponse
     */
    public function show(SaleEvent $saleEvent): SaleEventResource|JsonResponse
    {
        try {
            $saleEvent->load('items.variant');

            return new SaleEventResource($saleEvent);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_SALE_EVENT');
        }
    }

    /**
     * Update an existing sale event.
     *
     * @param  Request    $request
     * @param  SaleEvent  $saleEvent
     * @return SaleEventResource|JsonResponse
     */
    public function update(Request $request, SaleEvent $saleEvent): SaleEventResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'             => ['sometimes', 'string', 'max:255'],
                'slug'             => ['sometimes', 'string', 'max:255', 'unique:sale_events,slug,' . $saleEvent->id],
                'description'      => ['nullable', 'string'],
                'type'             => ['sometimes', 'in:flash_sale,drop_launch,clearance,bundle'],
                'status'           => ['sometimes', 'in:draft,scheduled,active,ended,cancelled'],
                'starts_at'        => ['sometimes', 'date'],
                'ends_at'          => ['nullable', 'date'],
                'max_orders_total' => ['nullable', 'integer', 'min:1'],
                'is_public'        => ['nullable', 'boolean'],
                'banner_url'       => ['nullable', 'string', 'max:500'],
            ]);

            $saleEvent->update($validated);

            return new SaleEventResource($saleEvent->fresh()->load('items.variant'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_SALE_EVENT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_SALE_EVENT');
        }
    }

    /**
     * Soft-delete a sale event.
     *
     * @param  SaleEvent  $saleEvent
     * @return JsonResponse
     */
    public function destroy(SaleEvent $saleEvent): JsonResponse
    {
        try {
            $saleEvent->delete();

            return response()->json(['success' => true, 'message' => 'Sale event deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_SALE_EVENT');
        }
    }

    /**
     * Activate a sale event via ActivateSaleEventAction.
     *
     * Transitions the event to 'active' status and writes sale prices
     * to all participating product variants atomically.
     *
     * @param  SaleEvent               $saleEvent
     * @param  ActivateSaleEventAction  $action
     * @return SaleEventResource|JsonResponse
     */
    public function activate(SaleEvent $saleEvent, ActivateSaleEventAction $action): SaleEventResource|JsonResponse
    {
        try {
            $event = $action->execute($saleEvent);

            return new SaleEventResource($event->load('items.variant'));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'ACTIVATE_SALE_EVENT');
        }
    }

    /**
     * End an active sale event via EndSaleEventAction.
     *
     * Transitions the event to 'ended' status and clears sale price
     * overrides from all participating product variants atomically.
     *
     * @param  SaleEvent          $saleEvent
     * @param  EndSaleEventAction  $action
     * @return SaleEventResource|JsonResponse
     */
    public function end(SaleEvent $saleEvent, EndSaleEventAction $action): SaleEventResource|JsonResponse
    {
        try {
            $event = $action->execute($saleEvent);

            return new SaleEventResource($event->load('items.variant'));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'END_SALE_EVENT');
        }
    }
}
