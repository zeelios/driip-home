<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Actions\CreateShipmentAction;
use App\Domain\Shipment\Actions\SyncTrackingAction;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\CourierServiceInterface;
use App\Domain\Shipment\Services\GHNService;
use App\Domain\Shipment\Services\GHTKService;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Shipment\CreateShipmentRequest;
use App\Http\Resources\Shipment\ShipmentResource;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manages shipments — creation, retrieval, cancellation, tracking sync, and label retrieval.
 *
 * All mutations delegate to dedicated Action classes. This controller stays thin.
 */
class ShipmentController extends BaseApiController
{
    /**
     * Map of courier codes to their service class names.
     *
     * @var array<string,class-string<CourierServiceInterface>>
     */
    private array $courierMap = [
        'ghn'  => GHNService::class,
        'ghtk' => GHTKService::class,
    ];

    /**
     * @param CreateShipmentAction $createShipment Action responsible for creating a shipment.
     * @param SyncTrackingAction   $syncTracking   Action responsible for syncing tracking events.
     */
    public function __construct(
        private readonly CreateShipmentAction $createShipment,
        private readonly SyncTrackingAction   $syncTracking,
    ) {}

    /**
     * List shipments with optional courier_code and status filters.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $shipments = QueryBuilder::for(Shipment::class)
            ->allowedFilters('courier_code', 'status', 'order_id')
            ->allowedSorts('created_at', 'delivered_at', 'estimated_delivery_at')
            ->with(['order', 'createdBy'])
            ->paginate($request->integer('per_page', 20));

        return ShipmentResource::collection($shipments);
    }

    /**
     * Show a single shipment with its tracking events.
     *
     * @param  Shipment  $shipment
     * @return ShipmentResource
     */
    public function show(Shipment $shipment): ShipmentResource
    {
        return new ShipmentResource(
            $shipment->load(['order', 'trackingEvents', 'createdBy'])
        );
    }

    /**
     * Cancel a shipment by calling the courier API and marking status as cancelled.
     *
     * @param  Shipment  $shipment
     * @return JsonResponse
     */
    public function destroy(Shipment $shipment): JsonResponse
    {
        try {
            if ($shipment->isDelivered()) {
                return $this->forbidden('CANCEL_SHIPMENT', 'Cannot cancel a delivered shipment.');
            }

            if (!empty($shipment->tracking_number)) {
                $serviceClass = $this->courierMap[$shipment->courier_code] ?? null;

                if ($serviceClass !== null) {
                    /** @var CourierServiceInterface $service */
                    $service = Container::getInstance()->make($serviceClass);
                    $service->cancelShipment($shipment->tracking_number);
                }
            }

            $shipment->update(['status' => 'cancelled']);

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CANCEL_SHIPMENT');
        }
    }

    /**
     * Trigger a tracking sync for the given shipment.
     *
     * @param  Shipment  $shipment
     * @return ShipmentResource|JsonResponse
     */
    public function syncTracking(Shipment $shipment): ShipmentResource|JsonResponse
    {
        try {
            $updated = $this->syncTracking->execute($shipment);

            return new ShipmentResource($updated->load(['order', 'trackingEvents', 'createdBy']));
        } catch (\RuntimeException $e) {
            return $this->notFound('SYNC_TRACKING', $e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SYNC_TRACKING');
        }
    }

    /**
     * Redirect to the shipment's label URL, or return 404 if no label exists.
     *
     * @param  Shipment  $shipment
     * @return RedirectResponse|JsonResponse
     */
    public function label(Shipment $shipment): RedirectResponse|JsonResponse
    {
        if (empty($shipment->label_url)) {
            return $this->notFound('GET_SHIPMENT_LABEL', 'No label available for this shipment.');
        }

        return redirect()->away($shipment->label_url);
    }
}
