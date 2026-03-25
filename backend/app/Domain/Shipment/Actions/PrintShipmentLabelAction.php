<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Render and persist a print-ready A7 shipment label.
 *
 * The courier-provided label metadata is stored on the shipment record and the
 * print action produces a thermal-friendly PDF that includes our logo, a QR
 * code, and the shipment summary needed for packing.
 */
class PrintShipmentLabelAction
{
    /**
     * Render the shipment label and store it on the public disk.
     *
     * @param  Shipment  $shipment
     * @return array<string,mixed>
     */
    public function execute(Shipment $shipment): array
    {
        $shipment->loadMissing(['order.customer', 'order.items.variant', 'createdBy']);

        $labelReference = $this->labelReference($shipment);
        $storagePath = $this->storagePath($shipment);
        $publicPath = 'shipment-labels/' . basename($storagePath);
        $logoDataUri = $this->logoDataUri();
        $qrValue = $this->qrValue($shipment, $labelReference);

        $directory = dirname($storagePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Pdf::view('pdf.shipment-label', [
            'shipment' => $shipment,
            'order' => $shipment->order,
            'labelReference' => $labelReference,
            'qrValue' => $qrValue,
            'logoDataUri' => $logoDataUri,
            'labelPayload' => $shipment->label_payload ?? $shipment->courier_response ?? [],
        ])
            ->format('a7')
            ->save($storagePath);

        $publicUrl = rtrim((string) config('app.url'), '/') . '/storage/' . $publicPath;

        $shipment->update([
            'label_url' => $publicUrl,
            'label_printed_at' => now(),
            'label_reference' => $labelReference,
            'label_payload' => $shipment->label_payload ?? $shipment->courier_response,
        ]);

        return [
            'success' => true,
            'filename' => $publicPath,
            'path' => $storagePath,
            'url' => $publicUrl,
            'label_reference' => $labelReference,
            'qr_value' => $qrValue,
        ];
    }

    /**
     * Determine the label reference shown on the printable PDF.
     */
    private function labelReference(Shipment $shipment): string
    {
        return (string) ($shipment->label_reference
            ?? $shipment->tracking_number
            ?? $shipment->internal_reference
            ?? $shipment->id);
    }

    /**
     * Determine the QR payload for the printable label.
     */
    private function qrValue(Shipment $shipment, string $labelReference): string
    {
        $orderNumber = $shipment->order?->order_number;

        if ($orderNumber !== null && $orderNumber !== '') {
            return $orderNumber . '|' . $labelReference;
        }

        return $labelReference;
    }

    /**
     * Resolve the label PDF storage path.
     */
    private function storagePath(Shipment $shipment): string
    {
        return storage_path('app/public/shipment-labels/' . $shipment->id . '.pdf');
    }

    /**
     * Resolve the company logo to a data URI for PDF rendering.
     */
    private function logoDataUri(): ?string
    {
        $candidates = [
            base_path('../home/public/logo.png'),
            base_path('../panel/public/logo.png'),
            base_path('public/logo.png'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/png';
                $contents = file_get_contents($candidate);

                if ($contents !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($contents);
                }
            }
        }

        return null;
    }
}
