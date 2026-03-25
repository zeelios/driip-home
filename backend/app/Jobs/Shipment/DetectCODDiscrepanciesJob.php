<?php

declare(strict_types=1);

namespace App\Jobs\Shipment;

use App\Domain\Shipment\Actions\DetectShipmentCODDiscrepancyAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to detect COD discrepancies across all shipments.
 *
 * Runs daily to find shipments where courier claims payment
 * but Driip hasn't received the remittance.
 */
class DetectCODDiscrepanciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?string $courierCode = null,
        private readonly int $daysBack = 30
    ) {
    }

    public function handle(DetectShipmentCODDiscrepancyAction $detector): void
    {
        Log::info('Starting COD discrepancy detection', [
            'courier' => $this->courierCode ?? 'all',
            'days_back' => $this->daysBack,
        ]);

        $result = $detector->detectAll($this->daysBack, $this->courierCode);

        Log::info('COD discrepancy detection completed', [
            'checked' => $result['total_checked'],
            'created' => $result['created'],
        ]);

        // TODO: Send notification if new discrepancies found
        // $this->notifyIfDiscrepanciesFound($result['created']);
    }
}
