<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentTrackingEvent;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles inbound courier webhook notifications.
 *
 * Each method processes a webhook push from a specific courier,
 * locates the matching internal Shipment by tracking_number,
 * updates its status, and creates a ShipmentTrackingEvent record.
 *
 * Webhooks return HTTP 200 quickly to prevent courier retries. Any
 * exceptions are caught and reported without propagating to the courier.
 */
class WebhookController extends BaseApiController
{
    /**
     * Process an inbound GHN (Giao Hang Nhanh) webhook.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function ghn(Request $request): JsonResponse
    {
        try {
            $payload        = $request->all();
            $trackingNumber = $payload['order_code'] ?? null;
            $courierStatus  = $payload['status']     ?? null;
            $message        = $payload['description'] ?? $courierStatus ?? 'GHN webhook event';

            $this->processWebhookEvent(
                courierCode:    'ghn',
                trackingNumber: $trackingNumber,
                status:         $this->mapGHNStatus($courierStatus),
                courierStatus:  $courierStatus,
                message:        $message,
                location:       null,
                rawData:        $payload,
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => true]);
        }
    }

    /**
     * Process an inbound GHTK (Giao Hang Tiet Kiem) webhook.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function ghtk(Request $request): JsonResponse
    {
        try {
            $payload        = $request->all();
            $trackingNumber = $payload['label_id']      ?? null;
            $courierStatus  = (string) ($payload['status_id'] ?? '');
            $message        = $payload['reason']        ?? $courierStatus ?? 'GHTK webhook event';

            $this->processWebhookEvent(
                courierCode:    'ghtk',
                trackingNumber: $trackingNumber,
                status:         $this->mapGHTKStatus($courierStatus),
                courierStatus:  $courierStatus,
                message:        $message,
                location:       null,
                rawData:        $payload,
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => true]);
        }
    }

    /**
     * Process an inbound SPX (Shopee Express) webhook.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function spx(Request $request): JsonResponse
    {
        try {
            $payload        = $request->all();
            $trackingNumber = $payload['tracking_no']   ?? null;
            $courierStatus  = $payload['order_status']  ?? null;
            $message        = $payload['description']   ?? $courierStatus ?? 'SPX webhook event';

            $this->processWebhookEvent(
                courierCode:    'spx',
                trackingNumber: $trackingNumber,
                status:         $this->mapGenericStatus($courierStatus),
                courierStatus:  $courierStatus,
                message:        $message,
                location:       $payload['location'] ?? null,
                rawData:        $payload,
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => true]);
        }
    }

    /**
     * Process an inbound Viettel Post webhook.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function viettel(Request $request): JsonResponse
    {
        try {
            $payload        = $request->all();
            $trackingNumber = $payload['ORDER_NUMBER'] ?? null;
            $courierStatus  = $payload['ORDER_STATUS'] ?? null;
            $message        = $payload['NOTE']         ?? $courierStatus ?? 'Viettel webhook event';

            $this->processWebhookEvent(
                courierCode:    'viettel',
                trackingNumber: $trackingNumber,
                status:         $this->mapGenericStatus($courierStatus),
                courierStatus:  $courierStatus,
                message:        $message,
                location:       $payload['CURRENT_WAREHOUSE'] ?? null,
                rawData:        $payload,
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => true]);
        }
    }

    /**
     * Find the shipment, update its status, and create a tracking event record.
     *
     * @param  string       $courierCode     Internal courier code (e.g. 'ghn').
     * @param  string|null  $trackingNumber  Courier-assigned tracking number.
     * @param  string       $status          Mapped internal status.
     * @param  string|null  $courierStatus   Raw courier status code.
     * @param  string       $message         Human-readable event description.
     * @param  string|null  $location        Optional location string.
     * @param  array<string,mixed>  $rawData Full webhook payload.
     * @return void
     */
    private function processWebhookEvent(
        string  $courierCode,
        ?string $trackingNumber,
        string  $status,
        ?string $courierStatus,
        string  $message,
        ?string $location,
        array   $rawData,
    ): void {
        if (empty($trackingNumber)) {
            return;
        }

        /** @var Shipment|null $shipment */
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->where('courier_code', $courierCode)
            ->first();

        if ($shipment === null) {
            return;
        }

        ShipmentTrackingEvent::create([
            'shipment_id'         => $shipment->id,
            'status'              => $status,
            'courier_status_code' => $courierStatus,
            'message'             => $message,
            'location'            => $location,
            'occurred_at'         => now(),
            'synced_at'           => now(),
            'raw_data'            => $rawData,
        ]);

        $updateData = ['status' => $status];

        if ($status === 'delivered' && $shipment->delivered_at === null) {
            $updateData['delivered_at'] = now();
        }

        if ($status === 'failed_delivery') {
            $updateData['failed_attempts'] = $shipment->failed_attempts + 1;
        }

        $shipment->update($updateData);
    }

    /**
     * Map a GHN courier status string to an internal shipment status.
     *
     * @param  string|null  $courierStatus
     * @return string
     */
    private function mapGHNStatus(?string $courierStatus): string
    {
        return match ($courierStatus) {
            'ready_to_pick', 'picking'              => 'created',
            'money_collect_picking'                 => 'pickup_scheduled',
            'picked', 'money_collect'               => 'picked_up',
            'storing', 'transporting', 'sorting'    => 'in_transit',
            'delivering'                            => 'out_for_delivery',
            'delivered'                             => 'delivered',
            'delivery_fail'                         => 'failed_delivery',
            'waiting_to_return', 'return'           => 'returning',
            'return_transporting', 'return_sorting' => 'returning',
            'returned'                              => 'returned',
            'cancel'                                => 'cancelled',
            default                                 => 'in_transit',
        };
    }

    /**
     * Map a GHTK status code to an internal shipment status.
     *
     * @param  string|null  $courierStatus  Numeric status code as string.
     * @return string
     */
    private function mapGHTKStatus(?string $courierStatus): string
    {
        return match ($courierStatus) {
            '1'  => 'created',
            '2'  => 'pickup_scheduled',
            '3'  => 'picked_up',
            '4'  => 'in_transit',
            '5'  => 'out_for_delivery',
            '9'  => 'delivered',
            '10' => 'failed_delivery',
            '6'  => 'returning',
            '13' => 'returned',
            '20' => 'cancelled',
            default => 'in_transit',
        };
    }

    /**
     * Map a generic courier status string to an internal shipment status.
     *
     * Used for couriers (SPX, Viettel) whose mappings are not yet fully documented.
     *
     * @param  string|null  $courierStatus
     * @return string
     */
    private function mapGenericStatus(?string $courierStatus): string
    {
        $normalized = strtolower((string) $courierStatus);

        return match (true) {
            str_contains($normalized, 'created')   => 'created',
            str_contains($normalized, 'picked')    => 'picked_up',
            str_contains($normalized, 'transit')   => 'in_transit',
            str_contains($normalized, 'delivering') => 'out_for_delivery',
            str_contains($normalized, 'delivered') => 'delivered',
            str_contains($normalized, 'fail')      => 'failed_delivery',
            str_contains($normalized, 'return')    => 'returning',
            str_contains($normalized, 'cancel')    => 'cancelled',
            default                                => 'in_transit',
        };
    }
}
