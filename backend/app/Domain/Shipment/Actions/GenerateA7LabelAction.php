<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Models\Shipment;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Generate custom A7 thermal printer shipping label (74mm x 105mm).
 *
 * Creates a compact label with QR code, tracking number, addresses,
 * and company branding optimized for thermal printers.
 */
class GenerateA7LabelAction
{
    /**
     * A7 size in millimeters: 74mm x 105mm.
     * Converted to points (1mm = 2.83465pt) for PDF: approximately 209.8pt x 297.6pt.
     */
    private const WIDTH_MM = 74;
    private const HEIGHT_MM = 105;

    /**
     * Generate A7 label for a shipment.
     *
     * @param  Shipment  $shipment  The shipment to generate label for.
     * @param  string|null  $courierLabelData  Optional courier label data (tracking number, barcode, etc.).
     * @return array<string,mixed>  Result with file path, URL, and metadata.
     *
     * @throws \RuntimeException On generation error.
     */
    public function execute(Shipment $shipment, ?string $courierLabelData = null): array
    {
        $order = $shipment->order;
        $trackingNumber = $shipment->tracking_number ?? 'N/A';

        // Generate QR code data
        $qrData = $this->generateQrData($shipment);

        // Build HTML for A7 label
        $html = $this->buildLabelHtml($shipment, $qrData, $courierLabelData);

        // Generate filename
        $filename = "labels/a7_{$shipment->id}_{$trackingNumber}.pdf";
        $fullPath = storage_path('app/public/' . $filename);

        // Ensure directory exists
        $labelsDir = storage_path('app/public/labels');
        if (!is_dir($labelsDir)) {
            mkdir($labelsDir, 0755, true);
        }

        // Generate PDF with A7 dimensions
        Pdf::html($html)
            ->withOption('format', [self::WIDTH_MM . 'mm', self::HEIGHT_MM . 'mm'])
            ->withOption('margin', ['top' => '2mm', 'right' => '2mm', 'bottom' => '2mm', 'left' => '2mm'])
            ->save($fullPath);

        // Store to public disk for access
        Storage::disk('public')->put($filename, file_get_contents($fullPath));

        $url = config('app.url') . '/storage/' . $filename;

        return [
            'success' => true,
            'shipment_id' => $shipment->id,
            'tracking_number' => $trackingNumber,
            'filename' => $filename,
            'url' => $url,
            'size' => 'A7 (74mm x 105mm)',
            'format' => 'thermal',
        ];
    }

    /**
     * Generate QR code data containing shipment info.
     */
    private function generateQrData(Shipment $shipment): string
    {
        $data = [
            't' => $shipment->tracking_number,
            'o' => $shipment->order?->order_number,
            'c' => $shipment->courier_code,
        ];

        return base64_encode(json_encode($data));
    }

    /**
     * Build HTML content for the A7 label.
     */
    private function buildLabelHtml(Shipment $shipment, string $qrData, ?string $courierLabelData): string
    {
        $order = $shipment->order;
        $trackingNumber = $shipment->tracking_number ?? 'N/A';
        $courier = strtoupper($shipment->courier_code ?? 'COURIER');

        $companyName = config('app.company.name', 'DRIP STORE');
        $companyAddress = config('app.company.address', '123 Store Street');
        $companyDistrict = config('app.company.district', 'District');
        $companyProvince = config('app.company.province', 'Province');

        $fromAddress = $this->formatAddress([
            $companyName,
            $companyAddress,
            $companyDistrict,
            $companyProvince,
        ]);

        $shippingName = $order?->shipping_name ?? $order?->guest_name ?? 'Customer';
        $shippingAddress = $order?->shipping_address ?? '';
        $shippingWard = $order?->shipping_ward ?? '';
        $shippingDistrict = $order?->shipping_district ?? '';
        $shippingProvince = $order?->shipping_province ?? '';

        $toAddress = $this->formatAddress([
            $shippingName,
            $shippingAddress,
            $shippingWard,
            $shippingDistrict,
            $shippingProvince,
        ]);

        $codAmount = $shipment->cod_amount > 0
            ? number_format($shipment->cod_amount) . ' VND'
            : 'Không thu COD';

        $orderNotes = $order?->notes ?? '';

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=' . urlencode($qrData);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 7pt;
            line-height: 1.2;
            color: #000;
            width: 70mm;
            height: 101mm;
            padding: 2mm;
        }
        .header {
            text-align: center;
            border-bottom: 0.5pt solid #000;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }
        .logo {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .courier {
            font-size: 6pt;
            color: #333;
        }
        .tracking {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
            margin: 1mm 0;
            padding: 1mm;
            border: 0.5pt solid #000;
        }
        .content {
            display: flex;
            gap: 2mm;
            margin: 1mm 0;
        }
        .qr-section {
            flex-shrink: 0;
        }
        .qr-section img {
            width: 20mm;
            height: 20mm;
        }
        .addresses {
            flex: 1;
            font-size: 6pt;
        }
        .address-block {
            margin-bottom: 1mm;
        }
        .address-label {
            font-weight: bold;
            font-size: 5pt;
            text-transform: uppercase;
            color: #333;
        }
        .cod-section {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            padding: 1mm;
            margin: 1mm 0;
            border: 1pt solid #000;
            background: #f0f0f0;
        }
        .notes {
            font-size: 5pt;
            color: #666;
            border-top: 0.5pt solid #ccc;
            padding-top: 1mm;
            margin-top: 1mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">DRIP</div>
        <div class="courier">{$courier}</div>
    </div>

    <div class="tracking">{$trackingNumber}</div>

    <div class="content">
        <div class="qr-section">
            <img src="{$qrUrl}" alt="QR">
        </div>
        <div class="addresses">
            <div class="address-block">
                <div class="address-label">Từ:</div>
                <div>{$fromAddress}</div>
            </div>
            <div class="address-block">
                <div class="address-label">Đến:</div>
                <div>{$toAddress}</div>
            </div>
        </div>
    </div>

    <div class="cod-section">{$codAmount}</div>

    <div class="notes">
        {$orderNotes}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Format address lines into a single string.
     */
    private function formatAddress(array $lines): string
    {
        $filtered = array_filter($lines, fn($line) => !empty($line));
        return implode(', ', array_slice($filtered, 0, 3));
    }
}
