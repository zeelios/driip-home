<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Actions\CreateShipmentAction;
use App\Domain\Shipment\Actions\PrintShipmentLabelAction;
use App\Domain\Shipment\Actions\SyncTrackingAction;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\CourierServiceInterface;
use App\Domain\Shipment\Services\CourierServiceResolver;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Shipment\ShipmentResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
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
     * @param CreateShipmentAction $createShipment Action responsible for creating a shipment.
     * @param SyncTrackingAction   $syncTracking   Action responsible for syncing tracking events.
     */
    public function __construct(
        private readonly CreateShipmentAction $createShipment,
        private readonly PrintShipmentLabelAction $printShipmentLabel,
        private readonly SyncTrackingAction $syncTracking,
        private readonly CourierServiceResolver $courierResolver,
    ) {
    }

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
            ->with(['order.customer', 'createdBy'])
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
                /** @var CourierServiceInterface $service */
                $service = $this->courierResolver->resolve($shipment->courier_code);
                $service->cancelShipment($shipment->tracking_number);
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
     * Render the shipment label as a printable PDF.
     *
     * @param  Shipment  $shipment
     * @return BinaryFileResponse|JsonResponse
     */
    public function label(Shipment $shipment): BinaryFileResponse|JsonResponse
    {
        try {
            $result = $this->printShipmentLabel->execute($shipment);

            return response()->file(
                $result['path'],
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="shipment-label-' . $shipment->id . '.pdf"',
                ]
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'GET_SHIPMENT_LABEL');
        }
    }
}
