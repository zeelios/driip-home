<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Actions\CalculateGhtkFeeAction;
use App\Domain\Shipment\Actions\CancelGhtkOrderAction;
use App\Domain\Shipment\Actions\GenerateA7LabelAction;
use App\Domain\Shipment\Actions\GetGhtkOrderStatusAction;
use App\Domain\Shipment\Actions\PrintGhtkLabelAction;
use App\Domain\Shipment\Actions\SubmitGhtkOrderAction;
use App\Domain\Shipment\Data\GhtkCalculateFeeDto;
use App\Domain\Shipment\Data\GhtkSubmitOrderDto;
use App\Domain\Shipment\Models\Shipment;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for GHTK courier operations.
 */
class GhtkController extends BaseApiController
{
    /**
     * Calculate shipping fee before creating order.
     */
    public function calculateFee(Request $request, CalculateGhtkFeeAction $action): JsonResponse
    {
        $validated = $request->validate([
            'pick_province' => 'required|string',
            'pick_district' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'address' => 'nullable|string',
            'weight' => 'nullable|integer|min:1',
            'value' => 'nullable|integer|min:0',
            'transport' => 'nullable|string|in:fly,road',
            'deliver_option' => 'nullable|string',
        ]);

        $dto = new GhtkCalculateFeeDto(
            pickProvince: $validated['pick_province'],
            pickDistrict: $validated['pick_district'],
            province: $validated['province'],
            district: $validated['district'],
            address: $validated['address'] ?? '',
            weight: $validated['weight'] ?? 1000,
            value: $validated['value'] ?? 0,
            transport: $validated['transport'] ?? null,
            deliverOption: $validated['deliver_option'] ?? null,
        );

        $result = $action->execute($dto);

        return response()->json([
            'data' => [
                'fee' => $result['fee'],
                'insurance_fee' => $result['insurance_fee'] ?? 0,
                'message' => $result['message'] ?? '',
                'success' => $result['success'],
            ],
        ]);
    }

    /**
     * Submit new order to GHTK.
     */
    public function submitOrder(Request $request, SubmitGhtkOrderAction $action): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.weight' => 'required|numeric|min:0.001',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.product_code' => 'nullable|string',
            'pick_name' => 'required|string|max:100',
            'pick_address' => 'required|string|max:255',
            'pick_province' => 'required|string|max:100',
            'pick_district' => 'required|string|max:100',
            'pick_ward' => 'required|string|max:100',
            'pick_tel' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'ward' => 'required|string|max:100',
            'tel' => 'required|string|max:20',
            'pick_money' => 'nullable|integer|min:0',
            'value' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:500',
            'transport' => 'nullable|string|in:fly,road',
            'pick_option' => 'nullable|string',
            'deliver_option' => 'nullable|string',
        ]);

        $dto = new GhtkSubmitOrderDto(
            products: $validated['products'],
            order: [
                'id' => $validated['order_id'],
                'pick_name' => $validated['pick_name'],
                'pick_address' => $validated['pick_address'],
                'pick_province' => $validated['pick_province'],
                'pick_district' => $validated['pick_district'],
                'pick_ward' => $validated['pick_ward'],
                'pick_tel' => $validated['pick_tel'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'province' => $validated['province'],
                'district' => $validated['district'],
                'ward' => $validated['ward'],
                'tel' => $validated['tel'],
                'hamlet' => 'Khác',
                'pick_money' => $validated['pick_money'] ?? 0,
                'value' => $validated['value'] ?? 0,
                'note' => $validated['note'] ?? '',
                'is_freeship' => ($validated['pick_money'] ?? 0) > 0 ? '0' : '1',
                'transport' => $validated['transport'] ?? 'fly',
                'pick_option' => $validated['pick_option'] ?? 'cod',
            ],
        );

        $result = $action->execute($dto, $validated['order_id']);

        return response()->json([
            'data' => [
                'shipment_id' => $result['shipment']->id,
                'tracking_number' => $result['tracking_number'],
                'fee' => $result['fee'],
                'estimated_pick_time' => $result['estimated_pick_time'],
                'estimated_deliver_time' => $result['estimated_deliver_time'],
            ],
            'message' => 'Order submitted to GHTK successfully',
        ], 201);
    }

    /**
     * Get order status from GHTK.
     */
    public function getOrderStatus(
        string $trackingNumber,
        GetGhtkOrderStatusAction $action
    ): JsonResponse {
        $status = $action->execute($trackingNumber);

        return response()->json([
            'data' => [
                'tracking_number' => $status['label_id'],
                'partner_id' => $status['partner_id'],
                'status' => $status['status'],
                'status_text' => $status['status_text'],
                'created' => $status['created'],
                'modified' => $status['modified'],
                'pick_date' => $status['pick_date'],
                'deliver_date' => $status['deliver_date'],
                'ship_money' => $status['ship_money'],
                'pick_money' => $status['pick_money'],
                'customer_name' => $status['customer_fullname'],
                'customer_phone' => $status['customer_tel'],
                'address' => $status['address'],
            ],
        ]);
    }

    /**
     * Sync order status to local shipment record.
     */
    public function syncStatus(
        Shipment $shipment,
        GetGhtkOrderStatusAction $action
    ): JsonResponse {
        if ($shipment->courier_code !== 'ghtk') {
            return response()->json([
                'error' => 'Shipment is not a GHTK shipment',
            ], 400);
        }

        $updated = $action->syncToShipment($shipment);

        return response()->json([
            'data' => [
                'shipment_id' => $updated->id,
                'tracking_number' => $updated->tracking_number,
                'status' => $updated->status,
                'courier_status' => $updated->courier_status,
                'delivered_at' => $updated->delivered_at?->toIso8601String(),
                'picked_up_at' => $updated->picked_up_at?->toIso8601String(),
            ],
            'message' => 'Status synced successfully',
        ]);
    }

    /**
     * Print shipping label.
     */
    public function printLabel(
        string $trackingNumber,
        PrintGhtkLabelAction $action
    ): Response {
        try {
            $result = $action->execute($trackingNumber);

            return response()->file(
                storage_path('app/public/' . $result['filename']),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="label_' . $trackingNumber . '.pdf"',
                ]
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to print label: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate custom A7 thermal label for shipment.
     */
    public function printA7Label(
        Shipment $shipment,
        GenerateA7LabelAction $action
    ): Response {
        try {
            $result = $action->execute($shipment);

            return response()->file(
                storage_path('app/public/' . $result['filename']),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="label_a7_' . $shipment->tracking_number . '.pdf"',
                ]
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to generate A7 label: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(
        string $trackingNumber,
        CancelGhtkOrderAction $action
    ): JsonResponse {
        $result = $action->execute($trackingNumber);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['message'],
                'data' => [
                    'tracking_number' => $trackingNumber,
                    'success' => false,
                    'log_id' => $result['log_id'],
                ],
            ], 400);
        }

        return response()->json([
            'data' => [
                'tracking_number' => $trackingNumber,
                'success' => true,
                'log_id' => $result['log_id'],
                'message' => $result['message'],
            ],
        ]);
    }

    /**
     * Cancel shipment and update record.
     */
    public function cancelShipment(
        Shipment $shipment,
        CancelGhtkOrderAction $action
    ): JsonResponse {
        if ($shipment->courier_code !== 'ghtk') {
            return response()->json([
                'error' => 'Shipment is not a GHTK shipment',
            ], 400);
        }

        $result = $action->cancelShipment($shipment);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['message'],
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'success' => false,
                ],
            ], 400);
        }

        return response()->json([
            'data' => [
                'shipment_id' => $result['shipment']->id,
                'tracking_number' => $result['shipment']->tracking_number,
                'status' => $result['shipment']->status,
                'cancelled_at' => $result['shipment']->cancelled_at?->toIso8601String(),
            ],
            'message' => 'Shipment cancelled successfully',
        ]);
    }
}
