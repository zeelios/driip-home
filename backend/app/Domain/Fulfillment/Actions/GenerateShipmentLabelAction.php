<?php

declare(strict_types=1);

namespace App\Domain\Fulfillment\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Shipment\Actions\SubmitGhtkOrderAction;
use App\Domain\Shipment\Data\GhtkSubmitOrderDto;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Support\Facades\Log;

/**
 * Action to generate shipping labels via courier APIs.
 *
 * Supports GHTK, GHN, and Viettel Post. Automatically selects
 * based on courier_code and updates shipment with tracking info.
 */
class GenerateShipmentLabelAction
{
    public function __construct(
        private readonly SubmitGhtkOrderAction $ghtkAction,
    ) {
    }

    /**
     * Generate a shipping label for the given shipment.
     *
     * @param  Shipment      $shipment  The shipment record
     * @param  Order         $order     The parent order
     * @param  iterable      $items     The items being shipped
     * @return array<string,mixed>|null Label result or null on failure
     */
    public function execute(Shipment $shipment, Order $order, iterable $items): ?array
    {
        return match ($shipment->courier_code) {
            'ghtk' => $this->generateGhtkLabel($shipment, $order, $items),
            'ghn' => $this->generateGhnLabel($shipment, $order, $items),
            'viettel_post' => $this->generateViettelPostLabel($shipment, $order, $items),
            default => null,
        };
    }

    /**
     * Generate GHTK shipping label.
     */
    private function generateGhtkLabel(Shipment $shipment, Order $order, iterable $items): ?array
    {
        try {
            $products = [];
            foreach ($items as $item) {
                $products[] = [
                    'name' => $item->name,
                    'weight' => ($item->product?->weight_grams ?? 500) / 1000, // Convert to kg
                    'quantity' => 1,
                    'product_code' => $item->sku,
                ];
            }

            // Get warehouse/pickup address from settings or default
            $pickupAddress = $this->getPickupAddress($order->warehouse_id);

            $dto = new GhtkSubmitOrderDto(
                products: $products,
                order: [
                    'id' => $order->id,
                    'pick_name' => $pickupAddress['name'] ?? 'Driip Store',
                    'pick_address' => $pickupAddress['address'] ?? '123 Main St',
                    'pick_province' => $pickupAddress['province'] ?? 'TP. Hồ Chí Minh',
                    'pick_district' => $pickupAddress['district'] ?? 'Quận 1',
                    'pick_ward' => $pickupAddress['ward'] ?? 'Phường Bến Nghé',
                    'pick_tel' => $pickupAddress['phone'] ?? '0901234567',
                    'name' => $order->shipping_name ?? $order->guest_name ?? 'Customer',
                    'address' => $order->shipping_address ?? 'Unknown',
                    'province' => $order->shipping_province ?? 'TP. Hồ Chí Minh',
                    'district' => $order->shipping_district ?? 'Quận 1',
                    'ward' => $order->shipping_ward ?? 'Phường 1',
                    'tel' => $order->shipping_phone ?? '0900000000',
                    'hamlet' => 'Khác',
                    'pick_money' => $order->payment_method === 'cod' ? $order->total_after_tax : 0,
                    'value' => $order->total_after_tax,
                    'note' => $order->notes ?? '',
                    'is_freeship' => $order->payment_method === 'cod' ? '0' : '1',
                    'transport' => 'fly',
                    'pick_option' => 'cod',
                ],
            );

            $result = $this->ghtkAction->execute($dto, $order->id);

            // Update shipment with tracking info
            $shipment->update([
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'] ?? null,
                'status' => 'label_created',
            ]);

            return [
                'courier' => 'ghtk',
                'tracking_number' => $result['tracking_number'],
                'fee' => $result['fee'],
                'label_url' => $result['label_url'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('GHTK label generation failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate GHN shipping label (placeholder for future implementation).
     */
    private function generateGhnLabel(Shipment $shipment, Order $order, iterable $items): ?array
    {
        // TODO: Implement GHN API integration
        Log::info('GHN label generation not yet implemented');
        return null;
    }

    /**
     * Generate Viettel Post shipping label (placeholder for future implementation).
     */
    private function generateViettelPostLabel(Shipment $shipment, Order $order, iterable $items): ?array
    {
        // TODO: Implement Viettel Post API integration
        Log::info('Viettel Post label generation not yet implemented');
        return null;
    }

    /**
     * Get pickup address for warehouse.
     */
    private function getPickupAddress(?string $warehouseId): array
    {
        // TODO: Load from warehouse settings or system config
        return [
            'name' => 'Driip Fulfillment Center',
            'address' => '123 Nguyễn Văn A',
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Phường Bến Nghé',
            'phone' => '0901234567',
        ];
    }
}
