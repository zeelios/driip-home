<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Actions\DetectShipmentCODDiscrepancyAction;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentCODDiscrepancy;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing shipment COD discrepancies.
 */
class ShipmentDiscrepancyController extends BaseApiController
{
    /**
     * List all discrepancies with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShipmentCODDiscrepancy::with(['shipment', 'order', 'resolver']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('courier_code')) {
            $query->where('courier_code', $request->input('courier_code'));
        }

        if ($request->filled('discrepancy_type')) {
            $query->where('discrepancy_type', $request->input('discrepancy_type'));
        }

        $discrepancies = $query->orderBy('detected_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $discrepancies->through(fn ($d) => [
                'id' => $d->id,
                'shipment_id' => $d->shipment_id,
                'order_id' => $d->order_id,
                'courier_code' => $d->courier_code,
                'tracking_number' => $d->tracking_number,
                'cod_amount' => $d->cod_amount,
                'discrepancy_type' => $d->discrepancy_type,
                'status' => $d->status,
                'description' => $d->description,
                'detected_at' => $d->detected_at->toIso8601String(),
                'resolved_at' => $d->resolved_at?->toIso8601String(),
                'order' => [
                    'id' => $d->order->id,
                    'order_number' => $d->order->order_number,
                ],
            ]),
        ]);
    }

    /**
     * Show a single discrepancy.
     */
    public function show(ShipmentCODDiscrepancy $discrepancy): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $discrepancy->id,
                'shipment_id' => $discrepancy->shipment_id,
                'order_id' => $discrepancy->order_id,
                'courier_code' => $discrepancy->courier_code,
                'tracking_number' => $discrepancy->tracking_number,
                'cod_amount' => $discrepancy->cod_amount,
                'discrepancy_type' => $discrepancy->discrepancy_type,
                'status' => $discrepancy->status,
                'description' => $discrepancy->description,
                'courier_claim' => $discrepancy->courier_claim,
                'internal_record' => $discrepancy->internal_record,
                'resolution_notes' => $discrepancy->resolution_notes,
                'detected_at' => $discrepancy->detected_at->toIso8601String(),
                'resolved_at' => $discrepancy->resolved_at?->toIso8601String(),
                'resolver' => $discrepancy->resolver?->only(['id', 'name']),
                'shipment' => $discrepancy->shipment?->only(['id', 'status', 'delivered_at', 'cod_collected']),
                'order' => [
                    'id' => $discrepancy->order->id,
                    'order_number' => $discrepancy->order->order_number,
                    'customer_name' => $discrepancy->order->customer?->fullName() ?? $discrepancy->order->guest_name,
                ],
            ],
        ]);
    }

    /**
     * Mark a discrepancy as resolved.
     */
    public function resolve(Request $request, ShipmentCODDiscrepancy $discrepancy): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $discrepancy->markResolved(
            $request->input('resolution_notes'),
            $request->user()?->id
        );

        return response()->json([
            'data' => [
                'id' => $discrepancy->id,
                'status' => $discrepancy->status,
                'resolved_at' => $discrepancy->resolved_at->toIso8601String(),
            ],
            'message' => 'Discrepancy marked as resolved',
        ]);
    }

    /**
     * Mark a discrepancy as investigating.
     */
    public function investigate(Request $request, ShipmentCODDiscrepancy $discrepancy): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $discrepancy->markInvestigating($request->input('notes'));

        return response()->json([
            'data' => [
                'id' => $discrepancy->id,
                'status' => $discrepancy->status,
            ],
            'message' => 'Discrepancy marked as investigating',
        ]);
    }

    /**
     * Dismiss a discrepancy.
     */
    public function dismiss(Request $request, ShipmentCODDiscrepancy $discrepancy): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $discrepancy->markDismissed(
            $request->input('reason'),
            $request->user()?->id
        );

        return response()->json([
            'data' => [
                'id' => $discrepancy->id,
                'status' => $discrepancy->status,
                'resolved_at' => $discrepancy->resolved_at?->toIso8601String(),
            ],
            'message' => 'Discrepancy dismissed',
        ]);
    }

    /**
     * Get discrepancy summary/stats.
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'total_open' => ShipmentCODDiscrepancy::where('status', 'open')->count(),
            'total_investigating' => ShipmentCODDiscrepancy::where('status', 'investigating')->count(),
            'total_resolved' => ShipmentCODDiscrepancy::where('status', 'resolved')->count(),
            'total_dismissed' => ShipmentCODDiscrepancy::where('status', 'dismissed')->count(),
            'total_amount_at_risk' => ShipmentCODDiscrepancy::whereIn('status', ['open', 'investigating'])->sum('cod_amount'),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * List pending COD shipments (delivered but not remitted).
     */
    public function pendingCod(Request $request): JsonResponse
    {
        $query = Shipment::with(['order', 'trackingEvents'])
            ->where('status', 'delivered')
            ->where('cod_amount', '>', 0)
            ->where('cod_collected', false)
            ->whereNotNull('delivered_at');

        if ($request->filled('courier_code')) {
            $query->where('courier_code', $request->input('courier_code'));
        }

        $shipments = $query->orderBy('delivered_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $shipments->through(fn ($s) => [
                'id' => $s->id,
                'tracking_number' => $s->tracking_number,
                'courier_code' => $s->courier_code,
                'cod_amount' => $s->cod_amount,
                'delivered_at' => $s->delivered_at?->toIso8601String(),
                'days_since_delivery' => $s->delivered_at?->diffInDays(now()),
                'order' => [
                    'id' => $s->order->id,
                    'order_number' => $s->order->order_number,
                    'customer' => $s->order->customer?->only(['id', 'full_name', 'phone']),
                ],
            ]),
        ]);
    }

    /**
     * Force run discrepancy detection.
     */
    public function forceDetect(Request $request): JsonResponse
    {
        $request->validate([
            'courier_code' => 'nullable|string|in:ghtk,ghn',
            'days_back' => 'nullable|integer|min:1|max:90',
        ]);

        $detector = app(DetectShipmentCODDiscrepancyAction::class);

        $result = $detector->detectAll(
            $request->input('days_back', 30),
            $request->input('courier_code')
        );

        return response()->json([
            'data' => $result,
            'message' => "Detection complete. Checked {$result['total_checked']} shipments, found {$result['created']} new discrepancies.",
        ]);
    }
}
