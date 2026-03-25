<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Label</title>
    <style>
        @page {
            size: A7 portrait;
            margin: 4mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .label {
            display: flex;
            flex-direction: column;
            gap: 4mm;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            border: 1px solid #111827;
            border-radius: 4px;
            padding: 4mm;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 3mm;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 2.5mm;
            min-width: 0;
        }

        .brand img {
            width: 18mm;
            height: 18mm;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-name {
            font-size: 11pt;
            line-height: 1;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 6.5pt;
            color: #4b5563;
            margin-top: 1mm;
        }

        .label-ref {
            text-align: right;
            font-size: 7pt;
            line-height: 1.2;
        }

        .label-ref strong {
            display: block;
            font-size: 8.5pt;
        }

        .main {
            display: grid;
            grid-template-columns: 1fr 24mm;
            gap: 3mm;
            align-items: start;
            flex: 1 1 auto;
        }

        .panel {
            border: 1px solid #d1d5db;
            border-radius: 3px;
            padding: 2.5mm;
        }

        .section-title {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #374151;
            margin-bottom: 1.5mm;
        }

        .field {
            margin-bottom: 1.5mm;
            font-size: 7.5pt;
            line-height: 1.25;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        .field .label {
            display: block;
            font-size: 6pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.5mm;
        }

        .field .value {
            font-size: 8pt;
            font-weight: 600;
            word-break: break-word;
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 1.5mm;
        }

        .qr-box img {
            width: 22mm;
            height: 22mm;
            image-rendering: pixelated;
        }

        .qr-caption {
            font-size: 5.8pt;
            text-align: center;
            color: #4b5563;
            line-height: 1.15;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 3mm;
            font-size: 6.5pt;
            color: #4b5563;
            border-top: 1px dashed #d1d5db;
            padding-top: 2mm;
        }

        .footer strong {
            color: #111827;
        }
    </style>
</head>
<body>
@php
    $customer = $order?->customer;
    $recipientName = $customer?->fullName() ?? $order?->guest_name ?? 'Customer';
    $recipientPhone = $customer?->phone ?? $order?->guest_phone ?? '';
    $recipientAddress = $order?->shipping_address ?? 'N/A';
    $trackingNumber = $shipment->tracking_number ?? $labelReference;
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrValue);
@endphp

<div class="label">
    <div class="top">
        <div class="brand">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Logo">
            @endif
            <div class="brand-text">
                <div class="brand-name">{{ config('app.name', 'Driip') }}</div>
                <div class="brand-subtitle">A7 thermal shipment label</div>
            </div>
        </div>
        <div class="label-ref">
            <span>Tracking</span>
            <strong>{{ $trackingNumber }}</strong>
        </div>
    </div>

    <div class="main">
        <div class="panel">
            <div class="section-title">Recipient</div>
            <div class="field">
                <span class="label">Name</span>
                <span class="value">{{ $recipientName }}</span>
            </div>
            <div class="field">
                <span class="label">Phone</span>
                <span class="value">{{ $recipientPhone ?: '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Address</span>
                <span class="value">{{ $recipientAddress }}</span>
            </div>
            <div class="field">
                <span class="label">Courier</span>
                <span class="value">{{ strtoupper($shipment->courier_code) }}</span>
            </div>
            <div class="field">
                <span class="label">Label Ref</span>
                <span class="value">{{ $labelReference }}</span>
            </div>
        </div>

        <div class="qr-box">
            <img src="{{ $qrUrl }}" alt="QR Code">
            <div class="qr-caption">
                Scan to verify
                <br>
                {{ $labelReference }}
            </div>
        </div>
    </div>

    <div class="footer">
        <div>
            <strong>COD:</strong> {{ number_format((int) $shipment->cod_amount) }} VND
        </div>
        <div>
            <strong>Order:</strong> {{ $shipment->order?->order_number ?? '—' }}
        </div>
    </div>
</div>
</body>
</html>
