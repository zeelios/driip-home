<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use App\Domain\Order\Models\Order;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GHTK (Giao Hang Tiet Kiem) courier API integration.
 *
 * Implements real API calls to GHTK services for shipment creation,
 * tracking, cancellation, and COD remittance fetching.
 */
class GHTKService implements CourierServiceInterface
{
    private const TIMEOUT = 30;
    private const RETRY_TIMES = 3;
    private const RETRY_SLEEP_MS = 500;

    private string $baseUrl;
    private string $apiToken;
    private string $partnerCode;

    /**
     * GHTK status code to internal status mapping.
     *
     * @var array<int,string>
     */
    private array $statusMap = [
        -1 => 'cancelled',
        1 => 'created',
        2 => 'created',
        3 => 'picked_up',
        4 => 'in_transit',
        5 => 'in_transit',
        6 => 'out_for_delivery',
        7 => 'failed_delivery',
        8 => 'out_for_delivery',
        9 => 'delivered',
        10 => 'returning',
        11 => 'returned',
        12 => 'pending',
        13 => 'returning',
        20 => 'reconciled',
        21 => 'reconciled',
        22 => 'reconciled',
        31 => 'failed_delivery',
    ];

    public function __construct()
    {
        $sandboxMode = config('courier.ghtk.sandbox_mode', false);

        if ($sandboxMode) {
            $this->baseUrl = config('courier.ghtk.sandbox_endpoint', 'https://services-staging.ghtk.vn');
            $this->apiToken = config('courier.ghtk.sandbox_api_key', '');
        } else {
            $this->baseUrl = config('courier.ghtk.api_endpoint', 'https://services.giaohangtietkiem.vn');
            $this->apiToken = config('courier.ghtk.api_key', '');
        }

        $this->partnerCode = config('courier.ghtk.partner_code', '');
    }

    // ==================== ORDER MANAGEMENT ====================

    /**
     * Submit Order - Create a new shipment/order on GHTK platform.
     *
     * Endpoint: POST /services/shipment/order
     *
     * @param  array<string,mixed>  $orderData  Order data including products and order details
     * @return array<string,mixed>  Created order details with label_id, fee, etc.
     *
     * @throws \RuntimeException On API error.
     */
    public function submitOrder(array $orderData): array
    {
        $response = $this->request('post', '/services/shipment/order', $orderData);

        return [
            'success' => $response['success'] ?? false,
            'order' => $response['order'] ?? null,
            'message' => $response['message'] ?? '',
            'label_id' => $response['order']['label'] ?? null,
            'partner_id' => $response['order']['partner_id'] ?? null,
            'fee' => $response['order']['fee'] ?? 0,
            'insurance_fee' => $response['order']['insurance_fee'] ?? 0,
            'estimated_pick_time' => $response['order']['estimated_pick_time'] ?? null,
            'estimated_deliver_time' => $response['order']['estimated_deliver_time'] ?? null,
            'area' => $response['order']['area'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Create a shipment on the GHTK platform (implements CourierServiceInterface).
     *
     * @param  Shipment  $shipment  The internal shipment model.
     * @return array<string,mixed>  Courier response with tracking_number, label_url, etc.
     *
     * @throws \RuntimeException On API error.
     */
    public function createShipment(Shipment $shipment): array
    {
        $order = $shipment->order;
        $customer = $order?->customer;

        $orderData = [
            'products' => $this->buildProductsPayload($order),
            'order' => [
                'id' => $order?->order_number ?? 'ORDER-' . $shipment->id,
                'pick_name' => config('courier.ghtk.pick_name', 'Driip Store'),
                'pick_address' => config('courier.ghtk.pick_address', '123 Pick St'),
                'pick_province' => config('courier.ghtk.pick_province', 'Hà Nội'),
                'pick_district' => config('courier.ghtk.pick_district', 'Cầu Giấy'),
                'pick_ward' => config('courier.ghtk.pick_ward', 'Dịch Vọng'),
                'pick_tel' => config('courier.ghtk.pick_tel', '0912345678'),
                'name' => $customer?->fullName() ?? $order?->guest_name ?? 'Customer',
                'address' => $order?->shipping_address ?? 'Customer Address',
                'province' => $order?->shipping_province ?? 'Hồ Chí Minh',
                'district' => $order?->shipping_district ?? 'Quận 1',
                'ward' => $order?->shipping_ward ?? 'Phường Bến Nghé',
                'hamlet' => 'Khác',
                'tel' => $customer?->phone ?? $order?->guest_phone ?? '0987654321',
                'is_freeship' => $shipment->cod_amount > 0 ? '0' : '1',
                'pick_money' => $shipment->cod_amount,
                'note' => $order?->notes ?? '',
                'value' => $order?->total_after_tax ?? 0,
                'transport' => $shipment->transport_type ?? 'fly',
                'pick_option' => $shipment->pick_option ?? 'cod',
            ],
        ];

        $result = $this->submitOrder($orderData);
        $labelReference = $result['label_id'] ?? $result['partner_id'] ?? null;
        $labelPayload = null;

        if ($labelReference !== null) {
            try {
                $pdfContent = $this->printLabel((string) $labelReference);

                $labelPayload = [
                    'source' => 'ghtk',
                    'format' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'label_reference' => (string) $labelReference,
                    'content_base64' => base64_encode($pdfContent),
                ];
            } catch (
                \Throwable $e
            ) {
                Log::warning('Unable to fetch GHTK label payload after shipment creation', [
                    'tracking_number' => $labelReference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'courier' => 'ghtk',
            'tracking_number' => $result['label_id'],
            'label_url' => null,
            'label_reference' => $labelReference,
            'label_payload' => $labelPayload,
            'status' => $result['success'] ? 'created' : 'failed',
            'estimated_fee' => $result['fee'],
            'estimated_pick_time' => $result['estimated_pick_time'],
            'estimated_deliver_time' => $result['estimated_deliver_time'],
            'insurance_fee' => $result['insurance_fee'],
            'area' => $result['area'],
            'partner_id' => $result['partner_id'],
            'raw_response' => $result['raw_response'],
        ];
    }

    /**
     * Calculate shipping fee before creating order.
     *
     * Endpoint: GET /services/shipment/fee
     *
     * @param  array<string,mixed>  $params  Fee calculation parameters
     * @return array<string,mixed>  Fee details including total fee, insurance, etc.
     *
     * @throws \RuntimeException On API error.
     */
    public function calculateFee(array $params): array
    {
        $query = [
            'pick_province' => $params['pick_province'] ?? config('courier.ghtk.pick_province', 'Hà Nội'),
            'pick_district' => $params['pick_district'] ?? config('courier.ghtk.pick_district', 'Cầu Giấy'),
            'province' => $params['province'] ?? 'Hồ Chí Minh',
            'district' => $params['district'] ?? 'Quận 1',
            'address' => $params['address'] ?? '',
            'weight' => $params['weight'] ?? 1000,
            'value' => $params['value'] ?? 0,
        ];

        // Optional parameters
        if (isset($params['transport'])) {
            $query['transport'] = $params['transport'];
        }
        if (isset($params['deliver_option'])) {
            $query['deliver_option'] = $params['deliver_option'];
        }
        if (isset($params['tags'])) {
            $query['tags'] = $params['tags'];
        }

        $response = $this->request('get', '/services/shipment/fee', [], $query);

        return [
            'success' => $response['success'] ?? false,
            'fee' => $response['fee'] ?? 0,
            'insurance_fee' => $response['insurance_fee'] ?? 0,
            'message' => $response['message'] ?? '',
            'raw_response' => $response,
        ];
    }

    /**
     * Get order status and tracking information.
     *
     * Endpoint: GET /services/shipment/v2/{tracking_number}
     *
     * @param  string  $trackingNumber  GHTK label ID (e.g., "S1.A1.17373471")
     * @return array<string,mixed>  Order status details.
     *
     * @throws \RuntimeException On API error.
     */
    public function getOrderStatus(string $trackingNumber): array
    {
        $response = $this->request('get', "/services/shipment/v2/{$trackingNumber}");

        if (!isset($response['order'])) {
            throw new \RuntimeException("Order not found: {$trackingNumber}");
        }

        $order = $response['order'];

        return [
            'success' => $response['success'] ?? false,
            'label_id' => $order['label_id'] ?? $trackingNumber,
            'partner_id' => $order['partner_id'] ?? null,
            'status' => $order['status'] ?? null,
            'status_text' => $order['status_text'] ?? '',
            'created' => $order['created'] ?? null,
            'modified' => $order['modified'] ?? null,
            'pick_date' => $order['pick_date'] ?? null,
            'deliver_date' => $order['deliver_date'] ?? null,
            'customer_fullname' => $order['customer_fullname'] ?? '',
            'customer_tel' => $order['customer_tel'] ?? '',
            'address' => $order['address'] ?? '',
            'ship_money' => $order['ship_money'] ?? 0,
            'insurance' => $order['insurance'] ?? 0,
            'value' => $order['value'] ?? 0,
            'weight' => $order['weight'] ?? 0,
            'pick_money' => $order['pick_money'] ?? 0,
            'is_freeship' => $order['is_freeship'] ?? '0',
            'message' => $order['message'] ?? '',
            'storage_day' => $order['storage_day'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Retrieve tracking events for a shipment (implements CourierServiceInterface).
     *
     * @param  string  $trackingNumber  The GHTK tracking number.
     * @return array<int,array<string,mixed>>  List of tracking events.
     *
     * @throws \RuntimeException On API error.
     */
    public function getTrackingEvents(string $trackingNumber): array
    {
        try {
            $response = $this->request('get', "/services/shipment/v2/{$trackingNumber}");

            if (!isset($response['order'])) {
                Log::warning('GHTK tracking response missing order data', [
                    'tracking_number' => $trackingNumber,
                    'response' => $response,
                ]);

                return [];
            }

            $order = $response['order'];
            $events = [];

            $currentStatus = $this->mapStatus((int) ($order['status'] ?? 1));
            $events[] = [
                'status' => $currentStatus,
                'courier_status_code' => (string) ($order['status'] ?? '1'),
                'courier_status_text' => $order['status_text'] ?? 'Unknown',
                'message' => $order['status_text'] ?? 'Status update',
                'location' => $order['address'] ?? null,
                'occurred_at' => $order['modified'] ?? now()->toIso8601String(),
            ];

            usort($events, fn($a, $b) => $b['occurred_at'] <=> $a['occurred_at']);

            return $events;
        } catch (\Throwable $e) {
            Log::error('Failed to fetch GHTK tracking events', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel an order by GHTK tracking number.
     *
     * Endpoint: POST /services/shipment/cancel/{tracking_number}
     * Alternative: POST /services/shipment/cancel/partner_id:{partner_id}
     *
     * @param  string  $trackingNumber  GHTK label ID or partner_id:XXX format.
     * @return array<string,mixed>  Cancellation result with success flag and message.
     */
    public function cancelOrder(string $trackingNumber): array
    {
        $response = $this->request('post', "/services/shipment/cancel/{$trackingNumber}");

        return [
            'success' => $response['success'] ?? false,
            'message' => $response['message'] ?? '',
            'log_id' => $response['log_id'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Cancel a shipment (implements CourierServiceInterface).
     *
     * @param  string  $trackingNumber  The GHTK tracking number.
     * @return bool  True if cancellation was successful.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $result = $this->cancelOrder($trackingNumber);

            return $result['success'];
        } catch (\Throwable $e) {
            Log::error('Failed to cancel GHTK shipment', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Print label - Get PDF label for printing.
     *
     * Endpoint: GET /services/label/{label_id}
     *
     * @param  string  $labelId  GHTK label ID (e.g., "S1.8663516")
     * @return string  PDF binary content.
     *
     * @throws \RuntimeException On API error.
     */
    public function printLabel(string $labelId): string
    {
        $url = "{$this->baseUrl}/services/label/{$labelId}";

        $response = Http::withHeaders([
            'Token' => $this->apiToken,
            'X-Client-Source' => $this->partnerCode,
            'Accept' => 'application/pdf',
        ])->timeout(self::TIMEOUT)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "GHTK Label API error: HTTP {$response->status()}"
            );
        }

        // Check if response is JSON (error) or PDF (success)
        $contentType = $response->header('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $data = $response->json();
            throw new \RuntimeException(
                $data['message'] ?? 'Failed to generate label'
            );
        }

        return $response->body();
    }

    /**
     * Create pending order - Get a pending order number.
     *
     * Endpoint: POST /services/shipment/pending-order
     *
     * @return int|null  Pending order number or null on failure.
     */
    public function createPendingOrder(): ?int
    {
        try {
            $response = $this->request('post', '/services/shipment/pending-order');

            if ($response['success'] && isset($response['data']['pending_order'])) {
                return (int) $response['data']['pending_order'];
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Failed to create GHTK pending order', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ==================== COD REMITTANCE ====================

    /**
     * Fetch COD remittance list from GHTK.
     *
     * Endpoint: GET /services/kho-nhan/list
     *
     * @param  string  $from  Date from (YYYY-MM-DD).
     * @param  string  $to    Date to (YYYY-MM-DD).
     * @param  int|null $status  0=pending, 1=completed, null=all.
     * @return array<int,array<string,mixed>>  List of remittances.
     */
    public function fetchRemittanceList(string $from, string $to, ?int $status = null): array
    {
        $params = [
            'from' => $from,
            'to' => $to,
        ];

        if ($status !== null) {
            $params['status'] = $status;
        }

        $response = $this->request('get', '/services/kho-nhan/list', [], $params);

        return $response['data'] ?? [];
    }

    /**
     * Fetch COD remittance detail from GHTK.
     *
     * Endpoint: GET /services/kho-nhan/{remittance_id}
     *
     * @param  string  $remittanceId  GHTK remittance ID.
     * @return array<string,mixed>|null  Remittance detail with orders.
     */
    public function fetchRemittanceDetail(string $remittanceId): ?array
    {
        $response = $this->request('get', "/services/kho-nhan/{$remittanceId}");

        return $response['data'] ?? null;
    }

    // ==================== HELPER METHODS ====================

    private function request(string $method, string $endpoint, array $payload = [], array $query = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;
        $lastError = null;

        while ($attempt < self::RETRY_TIMES) {
            try {
                $http = Http::withHeaders([
                    'Token' => $this->apiToken,
                    'X-Client-Source' => $this->partnerCode,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(self::TIMEOUT);

                $response = strtolower((string) $method) === 'get'
                    ? $http->get($url, $query)
                    : $http->post($url, $payload);

                if (!$response->successful()) {
                    throw new \RuntimeException(
                        "GHTK API error: HTTP {$response->status()} - {$response->body()}"
                    );
                }

                $data = $response->json();

                if (isset($data['success']) && $data['success'] === false) {
                    $errorMessage = $data['message'] ?? 'Unknown error';
                    throw new \RuntimeException(
                        "GHTK API error: {$errorMessage}"
                    );
                }

                return $data ?? [];
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt < self::RETRY_TIMES) {
                    usleep(self::RETRY_SLEEP_MS * 1000 * $attempt);
                }
            }
        }

        throw new \RuntimeException(
            "GHTK API request failed after {$attempt} attempts: {$lastError->getMessage()}",
            0,
            $lastError
        );
    }

    private function mapStatus(int $ghtkStatus): string
    {
        return $this->statusMap[$ghtkStatus] ?? 'unknown';
    }

    private function buildProductsPayload(?Order $order): array
    {
        if ($order === null) {
            return [
                [
                    'name' => 'Product',
                    'weight' => 0.1,
                    'quantity' => 1,
                ],
            ];
        }

        return $order->items->map(fn($item) => [
            'name' => $item->product_name ?? 'Product',
            'weight' => ($item->weight_gram ?? 100) / 1000,
            'quantity' => $item->quantity ?? 1,
            'product_code' => $item->sku ?? '',
        ])->toArray();
    }
}
