<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Data\CreateShipmentDto;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\CourierServiceResolver;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new internal Shipment record and registers it with the courier.
 *
 * Resolves the correct CourierServiceInterface implementation from the
 * container based on the courier_code, calls the courier API, then persists
 * the courier's response (tracking number, label URL, etc.) back to the
 * Shipment row.
 */
class CreateShipmentAction
{
    public function __construct(
        private readonly CourierServiceResolver $courierResolver
    ) {
    }

    /**
     * Execute the shipment creation.
     *
     * Creates the Shipment row with status 'draft', calls the courier API,
     * then updates the row with the returned tracking number, label URL,
     * and status. All writes happen inside a database transaction.
     *
     * @param  CreateShipmentDto  $dto        Validated input data.
     * @param  string             $createdBy  UUID of the authenticated staff member.
     * @return Shipment  The fully populated, persisted shipment.
     *
     * @throws \RuntimeException  If the courier code is unsupported.
     * @throws \Throwable         On any other failure.
     */
    public function execute(CreateShipmentDto $dto, string $createdBy): Shipment
    {
        $courierService = $this->courierResolver->resolve($dto->courierCode);

        return DB::transaction(function () use ($dto, $createdBy, $courierService): Shipment {
            /** @var Shipment $shipment */
            $shipment = Shipment::create([
                'order_id' => $dto->orderId,
                'courier_code' => $dto->courierCode,
                'cod_amount' => $dto->codAmount,
                'weight_kg' => $dto->weightKg,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            $courierResponse = $courierService->createShipment($shipment);

            $shipment->update([
                'tracking_number' => $courierResponse['tracking_number'] ?? null,
                'label_url' => $courierResponse['label_url'] ?? null,
                'shipping_fee_quoted' => isset($courierResponse['estimated_fee'])
                    ? (int) $courierResponse['estimated_fee']
                    : null,
                'courier_response' => $courierResponse,
                'status' => $courierResponse['status'] === 'created' ? 'created' : 'draft',
            ]);

            return $shipment->refresh();
        });
    }
}
