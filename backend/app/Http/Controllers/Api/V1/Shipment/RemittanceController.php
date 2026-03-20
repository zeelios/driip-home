<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Actions\ConfirmRemittanceAction;
use App\Domain\Shipment\Actions\ReconcileRemittanceAction;
use App\Domain\Shipment\Models\CourierCODRemittance;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Shipment\CreateRemittanceRequest;
use App\Http\Requests\Shipment\ReconcileRemittanceRequest;
use App\Http\Resources\Shipment\CourierCODRemittanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages courier COD remittance records — listing, creation, reconciliation, and confirmation.
 */
class RemittanceController extends BaseApiController
{
    /**
     * @param ReconcileRemittanceAction $reconcileRemittance Action responsible for matching remittance items to shipments.
     * @param ConfirmRemittanceAction   $confirmRemittance   Action responsible for finalising a reconciled remittance.
     */
    public function __construct(
        private readonly ReconcileRemittanceAction $reconcileRemittance,
        private readonly ConfirmRemittanceAction   $confirmRemittance,
    ) {}

    /**
     * List remittances with optional courier_code, status, and date range filters.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CourierCODRemittance::query();

        if ($request->filled('courier_code')) {
            $query->where('courier_code', $request->input('courier_code'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('period_from', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('period_to', '<=', $request->input('date_to'));
        }

        $remittances = $query->latest()->paginate($request->integer('per_page', 20));

        return CourierCODRemittanceResource::collection($remittances);
    }

    /**
     * Create a new remittance record.
     *
     * @param  CreateRemittanceRequest  $request
     * @return CourierCODRemittanceResource|JsonResponse
     */
    public function store(CreateRemittanceRequest $request): CourierCODRemittanceResource|JsonResponse
    {
        try {
            $remittance = CourierCODRemittance::create($request->validated());

            return (new CourierCODRemittanceResource($remittance))->response()->setStatusCode(201);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_REMITTANCE');
        }
    }

    /**
     * Show a single remittance record with its line items.
     *
     * @param  CourierCODRemittance  $remittance
     * @return CourierCODRemittanceResource
     */
    public function show(CourierCODRemittance $remittance): CourierCODRemittanceResource
    {
        return new CourierCODRemittanceResource($remittance->load('items'));
    }

    /**
     * Reconcile a remittance by matching its items to internal shipments.
     *
     * @param  ReconcileRemittanceRequest  $request
     * @param  CourierCODRemittance        $remittance
     * @return CourierCODRemittanceResource|JsonResponse
     */
    public function reconcile(ReconcileRemittanceRequest $request, CourierCODRemittance $remittance): CourierCODRemittanceResource|JsonResponse
    {
        try {
            $dto     = $request->dto($remittance->id);
            $updated = $this->reconcileRemittance->execute($remittance, $dto);

            return new CourierCODRemittanceResource($updated);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'RECONCILE_REMITTANCE');
        }
    }

    /**
     * Confirm a remittance as fully reconciled (set status to 'reconciled').
     *
     * @param  CourierCODRemittance  $remittance
     * @return CourierCODRemittanceResource|JsonResponse
     */
    public function confirm(CourierCODRemittance $remittance): CourierCODRemittanceResource|JsonResponse
    {
        try {
            $updated = $this->confirmRemittance->execute($remittance);

            return new CourierCODRemittanceResource($updated);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CONFIRM_REMITTANCE');
        }
    }
}
