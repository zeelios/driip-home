<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Services\GHTKService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Action to print GHTK shipping label.
 */
class PrintGhtkLabelAction
{
    public function __construct(
        private readonly GHTKService $ghtkService
    ) {
    }

    /**
     * Execute label printing and storage.
     *
     * @param  string  $labelId  GHTK label ID (e.g., "S1.8663516")
     * @param  string|null  $shipmentId  Internal shipment ID for file naming
     * @return array<string,mixed>  Result with file path and URL.
     *
     * @throws \RuntimeException On API error.
     */
    public function execute(string $labelId, ?string $shipmentId = null): array
    {
        $pdfContent = $this->ghtkService->printLabel($labelId);

        // Ensure labels directory exists
        $labelsDir = storage_path('app/public/labels');
        if (!is_dir($labelsDir)) {
            mkdir($labelsDir, 0755, true);
        }

        // Generate filename
        $filename = $shipmentId
            ? "labels/ghtk_{$shipmentId}_{$labelId}.pdf"
            : "labels/ghtk_{$labelId}.pdf";

        // Store the PDF
        Storage::disk('public')->put($filename, $pdfContent);

        $url = config('app.url') . '/storage/' . $filename;

        Log::info('GHTK label printed and stored', [
            'label_id' => $labelId,
            'shipment_id' => $shipmentId,
            'filename' => $filename,
            'size' => strlen($pdfContent),
        ]);

        return [
            'success' => true,
            'label_id' => $labelId,
            'filename' => $filename,
            'url' => $url,
            'size' => strlen($pdfContent),
        ];
    }

    /**
     * Print label for existing shipment.
     *
     * @param  Shipment  $shipment
     * @return array<string,mixed>
     */
    public function forShipment(Shipment $shipment): array
    {
        if (empty($shipment->tracking_number)) {
            throw new \RuntimeException('Shipment has no tracking number');
        }

        return $this->execute($shipment->tracking_number, $shipment->id);
    }
}
