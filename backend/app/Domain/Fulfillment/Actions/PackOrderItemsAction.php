<?php

declare(strict_types=1);

namespace App\Domain\Fulfillment\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action to pack order items and generate shipping labels.
 *
 * Groups items by order, creates shipment records, generates courier labels,
 * and updates item status to 'packed' with tracking information.
 */
class PackOrderItemsAction
{
    public function __construct(
        private readonly GenerateShipmentLabelAction $labelAction,
    ) {
    }

    /**
     * Execute the pack action on selected order items.
     *
     * @param  list<string> $itemIds     Array of order item UUIDs
     * @param  string       $packedBy    User ID performing the pack
     * @param  string|null  $courierCode Optional courier override (ghtk, ghn, viettel_post)
     * @return array<string,mixed>       Result with counts, shipments, labels, items
     */
    public function execute(array $itemIds, string $packedBy, ?string $courierCode = null): array
    {
        return DB::transaction(function () use ($itemIds, $packedBy, $courierCode) {
            $items = OrderItem::whereIn('id', $itemIds)
                ->whereIn('status', ['pending', 'picked'])
                ->with(['order', 'product'])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException('No valid items found for packing');
            }

            // Group items by order
            $itemsByOrder = $items->groupBy('order_id');
            $shipments = [];
            $labels = [];

            foreach ($itemsByOrder as $orderId => $orderItems) {
                $order = $orderItems->first()->order;

                // Create shipment record
                $shipment = $this->createShipment($order, $orderItems, $packedBy, $courierCode);

                // Generate label if courier integration available
                $labelResult = $this->generateLabel($shipment, $order, $orderItems);

                if ($labelResult) {
                    $shipments[] = $shipment->fresh();
                    $labels[] = $labelResult;
                }

                // Update all items in this order
                /** @var \App\Domain\Order\Models\OrderItem $item */
                foreach ($orderItems as $item) {
                    $item->update([
                        'status' => 'packed',
                        'packed_at' => now(),
                        'packed_by' => $packedBy,
                        'shipment_id' => $shipment->id,
                    ]);
                }
            }

            return [
                'packed_count' => $items->count(),
                'shipment_count' => count($shipments),
                'shipments' => $shipments,
                'labels' => $labels,
                'items' => $items->fresh(['order', 'product', 'sizeOption', 'shipment']),
            ];
        });
    }

    /**
     * Create a shipment record for the order.
     */
    private function createShipment(Order $order, $items, string $packedBy, ?string $courierCode): Shipment
    {
        $warehouse = Warehouse::find($order->warehouse_id);
        $defaultCourier = $courierCode ?? config('couriers.default', 'ghtk');

        // Calculate total weight (default 500g per item if not specified)
        $totalWeight = $items->sum(fn($item) => $item->product?->weight_grams ?? 500);

        return Shipment::create([
            'order_id' => $order->id,
            'courier_code' => $defaultCourier,
            'tracking_number' => null, // Will be filled after label generation
            'status' => 'draft',
            'weight_grams' => $totalWeight,
            'package_count' => $items->count(),
            'label_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'created_by' => $packedBy,
        ]);
    }

    /**
     * Generate shipping label via courier API.
     */
    private function generateLabel(Shipment $shipment, Order $order, $items): ?array
    {
        try {
            return $this->labelAction->execute($shipment, $order, $items);
        } catch (\Throwable $e) {
            Log::warning('Failed to generate shipping label', [
                'shipment_id' => $shipment->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Return null to indicate label generation failed but packing continues
            return null;
        }
    }
}
