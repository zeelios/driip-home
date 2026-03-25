<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\GHTKService;
use Illuminate\Support\Facades\Log;

/**
 * Action to cancel a GHTK order.
 */
class CancelGhtkOrderAction
{
    public function __construct(
        private readonly GHTKService $ghtkService
    ) {
    }

    /**
     * Execute order cancellation.
     *
     * @param  string  $trackingNumber  GHTK label ID or partner_id:XXX format.
     * @return array<string,mixed>  Cancellation result.
     *
     * @throws \RuntimeException On API error.
     */
    public function execute(string $trackingNumber): array
    {
        $result = $this->ghtkService->cancelOrder($trackingNumber);

        if (!$result['success']) {
            Log::warning('GHTK order cancellation failed', [
                'tracking_number' => $trackingNumber,
                'message' => $result['message'],
            ]);
        } else {
            Log::info('GHTK order cancelled', [
                'tracking_number' => $trackingNumber,
                'log_id' => $result['log_id'],
            ]);
        }

        return $result;
    }

    /**
     * Cancel shipment and update record.
     *
     * @param  Shipment  $shipment
     * @return array<string,mixed>
     */
    public function cancelShipment(Shipment $shipment): array
    {
        if (empty($shipment->tracking_number)) {
            throw new \RuntimeException('Shipment has no tracking number');
        }

        $result = $this->execute($shipment->tracking_number);

        if ($result['success']) {
            $shipment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        return [
            ...$result,
            'shipment' => $shipment->refresh(),
        ];
    }
}
