<?php

declare(strict_types=1);

namespace App\Jobs\Shipment;

use App\Domain\Shipment\Models\CourierCODRemittance;
use App\Domain\Shipment\Models\CourierCODRemittanceItem;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\GHTKService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to fetch COD remittance data from GHTK.
 *
 * Fetches remittance batches and auto-reconciles with shipments.
 */
class FetchGHTKRemittanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo = null
    ) {
    }

    public function handle(GHTKService $ghtk): void
    {
        $from = $this->dateFrom ?? now()->subDay()->format('Y-m-d');
        $to = $this->dateTo ?? now()->format('Y-m-d');

        Log::info('Fetching GHTK remittances', ['from' => $from, 'to' => $to]);

        try {
            $remittances = $ghtk->fetchRemittanceList($from, $to);

            Log::info('Found GHTK remittances', ['count' => count($remittances)]);

            foreach ($remittances as $remittanceData) {
                $this->processRemittance($ghtk, $remittanceData);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to fetch GHTK remittances', [
                'error' => $e->getMessage(),
                'from' => $from,
                'to' => $to,
            ]);

            throw $e;
        }
    }

    private function processRemittance(GHTKService $ghtk, array $remittanceData): void
    {
        $remittanceId = $remittanceData['id'] ?? null;

        if (!$remittanceId) {
            return;
        }

        // Check if already imported
        $existing = CourierCODRemittance::where('remittance_reference', $remittanceId)->first();
        if ($existing && $existing->status !== 'pending') {
            return;
        }

        try {
            $detail = $ghtk->fetchRemittanceDetail($remittanceId);

            if (!$detail) {
                Log::warning('Could not fetch GHTK remittance detail', [
                    'remittance_id' => $remittanceId,
                ]);

                return;
            }

            DB::transaction(function () use ($remittanceId, $remittanceData, $detail, $existing) {
                $remittance = $existing ?? CourierCODRemittance::create([
                    'courier_code' => 'ghtk',
                    'remittance_reference' => $remittanceId,
                    'period_from' => $remittanceData['date'] ?? now()->subDay(),
                    'period_to' => $remittanceData['date'] ?? now(),
                    'total_cod_collected' => $remittanceData['total_money'] ?? 0,
                    'total_fees_deducted' => 0,
                    'net_remittance' => $remittanceData['total_money'] ?? 0,
                    'status' => 'pending',
                    'notes' => "Imported from GHTK. Orders: {$remittanceData['total_orders']}",
                ]);

                // Process order items
                $orders = $detail['orders'] ?? [];
                $totalFees = 0;

                foreach ($orders as $orderData) {
                    $trackingNumber = $orderData['label_id'] ?? null;
                    $codAmount = $orderData['money_collection'] ?? 0;
                    $shipFee = $orderData['ship_money'] ?? 0;
                    $totalFees += $shipFee;

                    if (!$trackingNumber) {
                        continue;
                    }

                    // Find matching shipment
                    $shipment = Shipment::where('tracking_number', $trackingNumber)
                        ->where('courier_code', 'ghtk')
                        ->first();

                    if ($shipment) {
                        CourierCODRemittanceItem::updateOrCreate(
                            [
                                'remittance_id' => $remittance->id,
                                'shipment_id' => $shipment->id,
                            ],
                            [
                                'order_id' => $shipment->order_id,
                                'cod_amount' => $codAmount,
                                'shipping_fee' => $shipFee,
                                'other_fees' => 0,
                                'net_amount' => $codAmount - $shipFee,
                            ]
                        );

                        // Mark shipment as COD collected
                        $shipment->update(['cod_collected' => true]);
                    }
                }

                // Update totals
                $remittance->update([
                    'total_fees_deducted' => $totalFees,
                    'net_remittance' => $remittance->total_cod_collected - $totalFees,
                    'status' => 'received',
                ]);
            });

            Log::info('Processed GHTK remittance', [
                'remittance_id' => $remittanceId,
                'orders' => count($detail['orders'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process GHTK remittance', [
                'remittance_id' => $remittanceId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
