<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use App\Domain\Order\Models\Order;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Support\Facades\Log;

/**
 * GHN (Giao Hàng Nhanh) courier API integration.
 *
 * Supports fee quotation, shipment creation, tracking sync, cancellation,
 * and status mapping for GHN orders.
 */
class GHNService extends AbstractCourierService implements CourierServiceInterface
{
    /**
     * @var array<string,string>
     */
    private array $statusMap = [
        'ready_to_pick' => 'created',
        'picking' => 'created',
        'picked' => 'picked_up',
        'storing' => 'in_transit',
        'transporting' => 'in_transit',
        'sorting' => 'in_transit',
        'delivering' => 'out_for_delivery',
        'delivered' => 'delivered',
        'delivery_fail' => 'failed_delivery',
        'waiting_to_return' => 'returning',
        'return' => 'returning',
        'return_transporting' => 'returning',
        'return_sorting' => 'returning',
        'returned' => 'returned',
        'cancel' => 'cancelled',
    ];

    public function __construct()
    {
        parent::__construct('ghn');
    }

    /**
     * GHN requires Token and ShopId headers on most business endpoints.
     *
     * @return array<string,string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Token' => $this->apiToken,
            'ShopId' => (string) config('courier.ghn.shop_id', ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Calculate fee before order creation.
     *
     * @param  array<string,mixed>  $params
     * @return array<string,mixed>
     */
    public function calculateFee(array $params): array
    {
        $payload = $this->buildFeePayload($params);
        $response = $this->request('post', '/v2/shipping-order/fee', $payload);

        $data = $response['data'] ?? [];

        return [
            'success' => (int) ($response['code'] ?? 0) === 200,
            'fee' => (int) ($data['total_fee'] ?? ($data['fee']['main_service'] ?? 0)),
            'insurance_fee' => (int) ($data['fee']['insurance'] ?? 0),
            'coupon_fee' => (int) ($data['fee']['coupon'] ?? 0),
            'main_service_fee' => (int) ($data['fee']['main_service'] ?? 0),
            'expected_delivery_time' => $data['expected_delivery_time'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Create a shipment on GHN.
     *
     * @param  Shipment  $shipment
     * @return array<string,mixed>
     */
    public function createShipment(Shipment $shipment): array
    {
        $payload = $this->buildCreateOrderPayload($shipment);
        $response = $this->request('post', '/v2/shipping-order/create', $payload);

        $data = $response['data'] ?? [];
        $trackingNumber = $data['order_code'] ?? null;

        return [
            'courier' => 'ghn',
            'tracking_number' => $trackingNumber,
            'label_url' => null,
            'status' => $trackingNumber ? 'created' : 'failed',
            'estimated_fee' => (int) ($data['total_fee'] ?? 0),
            'estimated_pick_time' => $data['expected_delivery_time'] ?? null,
            'estimated_deliver_time' => $data['expected_delivery_time'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Retrieve tracking events for a shipment from GHN.
     *
     * @param  string  $trackingNumber
     * @return array<int,array<string,mixed>>
     */
    public function getTrackingEvents(string $trackingNumber): array
    {
        $response = $this->request('post', '/v2/shipping-order/detail', [
            'order_code' => $trackingNumber,
        ]);

        $detail = $response['data'][0] ?? $response['data'] ?? null;

        if (!$detail || !is_array($detail)) {
            Log::warning('GHN tracking detail missing order data', [
                'tracking_number' => $trackingNumber,
                'response' => $response,
            ]);

            return [];
        }

        $events = [];

        foreach (($detail['log'] ?? []) as $entry) {
            $courierStatus = (string) ($entry['status'] ?? '');

            $events[] = [
                'status' => $this->mapStatus($courierStatus),
                'courier_status_code' => $courierStatus,
                'courier_status_text' => $courierStatus,
                'message' => $courierStatus,
                'location' => $detail['to_address'] ?? null,
                'occurred_at' => $entry['updated_date'] ?? now()->toIso8601String(),
            ];
        }

        if ($events === []) {
            $events[] = [
                'status' => $this->mapStatus((string) ($detail['status'] ?? 'ready_to_pick')),
                'courier_status_code' => (string) ($detail['status'] ?? 'ready_to_pick'),
                'courier_status_text' => (string) ($detail['status'] ?? 'ready_to_pick'),
                'message' => (string) ($detail['status'] ?? 'ready_to_pick'),
                'location' => $detail['to_address'] ?? null,
                'occurred_at' => $detail['order_date'] ?? now()->toIso8601String(),
            ];
        }

        usort($events, fn(array $a, array $b): int => strcmp((string) $b['occurred_at'], (string) $a['occurred_at']));

        return $events;
    }

    /**
     * Cancel a shipment on GHN.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        $response = $this->request('post', '/v2/switch-status/cancel', [
            'order_codes' => [$trackingNumber],
        ]);

        $data = $response['data'][0] ?? $response['data'] ?? null;

        return (bool) ($data['result'] ?? false);
    }

    /**
     * Create a GHN order payload from the shipment model.
     *
     * @return array<string,mixed>
     */
    private function buildCreateOrderPayload(Shipment $shipment): array
    {
        $order = $shipment->order;
        $pickup = $this->pickupConfig();

        return [
            'payment_type_id' => (int) ($pickup['payment_type_id'] ?? 2),
            'note' => $order?->notes ?? '',
            'required_note' => (string) ($pickup['required_note'] ?? 'KHONGCHOXEMHANG'),
            'from_name' => (string) ($pickup['from_name'] ?? config('app.name', 'Driip')),
            'from_phone' => (string) ($pickup['from_phone'] ?? ''),
            'from_address' => (string) ($pickup['from_address'] ?? ''),
            'from_ward_code' => (string) ($pickup['from_ward_code'] ?? ''),
            'from_district_id' => (int) ($pickup['from_district_id'] ?? 0),
            'return_name' => (string) ($pickup['return_name'] ?? ($pickup['from_name'] ?? config('app.name', 'Driip'))),
            'return_phone' => (string) ($pickup['return_phone'] ?? ($pickup['from_phone'] ?? '')),
            'return_address' => (string) ($pickup['return_address'] ?? ($pickup['from_address'] ?? '')),
            'return_ward_code' => (string) ($pickup['return_ward_code'] ?? ($pickup['from_ward_code'] ?? '')),
            'return_district_id' => (int) ($pickup['return_district_id'] ?? ($pickup['from_district_id'] ?? 0)),
            'client_order_code' => (string) ($order?->order_number ?? $shipment->id),
            'to_name' => $order?->customer?->fullName() ?? $order?->guest_name ?? 'Customer',
            'to_phone' => $order?->customer?->phone ?? $order?->guest_phone ?? '',
            'to_address' => $order?->shipping_address ?? '',
            'to_ward_code' => (string) ($order?->shipping_ward_code ?? $pickup['to_ward_code'] ?? ''),
            'to_district_id' => (int) ($order?->shipping_district_id ?? $pickup['to_district_id'] ?? 0),
            'cod_amount' => $shipment->cod_amount,
            'content' => $this->buildContent($order),
            'weight' => $this->weightInGrams($shipment, $order),
            'length' => (int) ($pickup['length'] ?? 0),
            'width' => (int) ($pickup['width'] ?? 0),
            'height' => (int) ($pickup['height'] ?? 0),
            'service_type_id' => (int) ($pickup['service_type_id'] ?? 2),
            'service_id' => (int) ($pickup['service_id'] ?? 0),
            'pick_station_id' => (int) ($pickup['pick_station_id'] ?? 0),
            'insurance_value' => (int) ($pickup['insurance_value'] ?? ($order?->total_after_tax ?? 0)),
            'coupon' => (string) ($pickup['coupon'] ?? ''),
            'pick_shift' => (array) ($pickup['pick_shift'] ?? []),
            'items' => $this->buildItemsPayload($order),
        ];
    }

    /**
     * Calculate pickup settings from courier config.
     *
     * @return array<string,mixed>
     */
    private function pickupConfig(): array
    {
        $pickupAddress = config('courier.ghn.pickup_address', []);

        if (!is_array($pickupAddress)) {
            $pickupAddress = [];
        }

        $settings = config('courier.ghn.settings', []);

        return array_merge($settings, $pickupAddress);
    }

    /**
     * Build the fee payload from generic input.
     *
     * @param  array<string,mixed>  $params
     * @return array<string,mixed>
     */
    private function buildFeePayload(array $params): array
    {
        $pickup = $this->pickupConfig();

        return [
            'service_type_id' => (int) ($params['service_type_id'] ?? $pickup['service_type_id'] ?? 2),
            'from_district_id' => (int) ($params['from_district_id'] ?? $pickup['from_district_id'] ?? 0),
            'from_ward_code' => (string) ($params['from_ward_code'] ?? $pickup['from_ward_code'] ?? ''),
            'to_district_id' => (int) ($params['to_district_id'] ?? 0),
            'to_ward_code' => (string) ($params['to_ward_code'] ?? ''),
            'height' => (int) ($params['height'] ?? $pickup['height'] ?? 0),
            'length' => (int) ($params['length'] ?? $pickup['length'] ?? 0),
            'width' => (int) ($params['width'] ?? $pickup['width'] ?? 0),
            'weight' => (int) ($params['weight'] ?? 0),
            'insurance_value' => (int) ($params['insurance_value'] ?? $params['value'] ?? 0),
            'cod_value' => (int) ($params['cod_value'] ?? $params['cod_amount'] ?? 0),
            'coupon' => (string) ($params['coupon'] ?? ''),
            'items' => $params['items'] ?? [],
        ];
    }

    /**
     * Build human-readable shipment items for GHN.
     *
     * @return array<int,array<string,mixed>>
     */
    private function buildItemsPayload(?Order $order): array
    {
        if ($order === null) {
            return [
                [
                    'name' => 'Product',
                    'code' => 'product',
                    'quantity' => 1,
                    'price' => 0,
                    'length' => 0,
                    'width' => 0,
                    'height' => 0,
                    'weight' => 100,
                ]
            ];
        }

        return $order->items->map(static fn($item): array => [
            'name' => $item->product_name ?? 'Product',
            'code' => $item->sku ?? '',
            'quantity' => (int) ($item->quantity ?? 1),
            'price' => (int) ($item->unit_price ?? 0),
            'length' => (int) ($item->length_cm ?? 0),
            'width' => (int) ($item->width_cm ?? 0),
            'height' => (int) ($item->height_cm ?? 0),
            'weight' => (int) ($item->weight_gram ?? 100),
            'category' => [
                'level1' => $item->category_level1 ?? '',
                'level2' => $item->category_level2 ?? '',
                'level3' => $item->category_level3 ?? '',
            ],
        ])->values()->all();
    }

    /**
     * Build a label for the order contents.
     */
    private function buildContent(?Order $order): string
    {
        if ($order === null) {
            return 'Product';
        }

        $names = $order->items->map(static fn($item): string => (string) ($item->product_name ?? 'Product'))
            ->filter()
            ->values()
            ->all();

        return $names === [] ? 'Product' : implode(', ', array_slice($names, 0, 4));
    }

    /**
     * Convert shipment weight to grams.
     */
    private function weightInGrams(Shipment $shipment, ?Order $order): int
    {
        if ($shipment->weight_kg !== null) {
            return max(1, (int) round((float) $shipment->weight_kg * 1000));
        }

        if ($order === null) {
            return 1000;
        }

        $total = $order->items->sum(static fn($item): int => (int) ($item->weight_gram ?? 100));

        return max(1, $total > 0 ? $total : 1000);
    }

    /**
     * Map GHN courier status strings to internal statuses.
     */
    private function mapStatus(string $courierStatus): string
    {
        return $this->statusMap[$courierStatus] ?? 'in_transit';
    }
}
