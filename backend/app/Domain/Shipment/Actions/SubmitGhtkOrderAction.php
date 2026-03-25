<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Data\GhtkCalculateFeeDto;
use App\Domain\Shipment\Data\GhtkSubmitOrderDto;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\GHTKService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action to submit an order to GHTK and create shipment record.
 */
class SubmitGhtkOrderAction
{
    public function __construct(
        private readonly GHTKService $ghtkService
    ) {
    }

    /**
     * Execute the order submission.
     *
     * @param  GhtkSubmitOrderDto  $dto
     * @param  string|null  $orderId  Internal order ID for association
     * @return array<string,mixed>  Result with success flag and shipment data.
     *
     * @throws \RuntimeException On API error.
     */
    public function execute(GhtkSubmitOrderDto $dto, ?string $orderId = null): array
    {
        return DB::transaction(function () use ($dto, $orderId): array {
            $result = $this->ghtkService->submitOrder($dto->toArray());

            if (!$result['success'] || empty($result['label_id'])) {
                Log::error('GHTK order submission failed', [
                    'result' => $result,
                    'order_id' => $orderId,
                ]);

                throw new \RuntimeException(
                    $result['message'] ?? 'GHTK order submission failed'
                );
            }

            // Create shipment record
            $shipment = Shipment::create([
                'order_id' => $orderId,
                'courier_code' => 'ghtk',
                'tracking_number' => $result['label_id'],
                'status' => 'created',
                'courier_status' => '1',
                'estimated_fee' => $result['fee'] ?? 0,
                'insurance_fee' => $result['insurance_fee'] ?? 0,
                'cod_amount' => $dto->order['pick_money'] ?? 0,
                'estimated_pick_time' => $result['estimated_pick_time'] ?? null,
                'estimated_deliver_time' => $result['estimated_deliver_time'] ?? null,
                'partner_id' => $result['partner_id'] ?? $dto->order['id'] ?? null,
                'raw_response' => $result['raw_response'],
            ]);

            Log::info('GHTK order submitted successfully', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $result['label_id'],
                'order_id' => $orderId,
                'fee' => $result['fee'] ?? 0,
            ]);

            return [
                'success' => true,
                'shipment' => $shipment,
                'tracking_number' => $result['label_id'],
                'fee' => $result['fee'] ?? 0,
                'estimated_pick_time' => $result['estimated_pick_time'],
                'estimated_deliver_time' => $result['estimated_deliver_time'],
            ];
        });
    }
}
