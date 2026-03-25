<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * DTO for GHTK Submit Order request.
 *
 * Represents the structure required for POST /services/shipment/order
 */
class GhtkSubmitOrderDto
{
    /**
     * @param  array<int,array<string,mixed>>  $products  List of products
     * @param  array<string,mixed>  $order  Order details
     */
    public function __construct(
        public readonly array $products,
        public readonly array $order,
    ) {
    }

    /**
     * Create from Shipment model with configuration.
     *
     * @param  \App\Domain\Shipment\Models\Shipment  $shipment
     * @param  array<string,mixed>  $config  GHTK config (pick_address, pick_name, etc.)
     * @return self
     */
    public static function fromShipment(\App\Domain\Shipment\Models\Shipment $shipment, array $config): self
    {
        $orderModel = $shipment->order;
        $customer = $orderModel?->customer;

        $products = $orderModel?->items->map(fn ($item) => [
            'name' => $item->product_name ?? 'Product',
            'weight' => ($item->weight_gram ?? 100) / 1000,
            'quantity' => $item->quantity ?? 1,
            'product_code' => $item->sku ?? '',
        ])->toArray() ?? [
            [
                'name' => 'Product',
                'weight' => 0.1,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'id' => $orderModel?->order_number ?? 'ORDER-' . $shipment->id,
            'pick_name' => $config['pick_name'] ?? 'Driip Store',
            'pick_address' => $config['pick_address'] ?? '',
            'pick_province' => $config['pick_province'] ?? 'Hà Nội',
            'pick_district' => $config['pick_district'] ?? 'Cầu Giấy',
            'pick_ward' => $config['pick_ward'] ?? 'Dịch Vọng',
            'pick_tel' => $config['pick_tel'] ?? '',
            'name' => $customer?->fullName() ?? $orderModel?->guest_name ?? 'Customer',
            'address' => $orderModel?->shipping_address ?? '',
            'province' => $orderModel?->shipping_province ?? 'Hồ Chí Minh',
            'district' => $orderModel?->shipping_district ?? 'Quận 1',
            'ward' => $orderModel?->shipping_ward ?? 'Phường Bến Nghé',
            'hamlet' => 'Khác',
            'tel' => $customer?->phone ?? $orderModel?->guest_phone ?? '',
            'is_freeship' => $shipment->cod_amount > 0 ? '0' : '1',
            'pick_money' => $shipment->cod_amount,
            'note' => $orderModel?->notes ?? '',
            'value' => $orderModel?->total_after_tax ?? 0,
            'transport' => $shipment->transport_type ?? 'fly',
            'pick_option' => $shipment->pick_option ?? 'cod',
        ];

        // Optional fields
        if ($shipment->deliver_option) {
            $orderData['deliver_option'] = $shipment->deliver_option;
        }
        if ($shipment->pick_date) {
            $orderData['pick_date'] = $shipment->pick_date;
        }
        if ($shipment->pick_session) {
            $orderData['pick_session'] = $shipment->pick_session;
        }
        if ($shipment->tags) {
            $orderData['tags'] = $shipment->tags;
        }

        return new self($products, $orderData);
    }

    /**
     * Convert to array for API request.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'order' => $this->order,
        ];
    }
}
