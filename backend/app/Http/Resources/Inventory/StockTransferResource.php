<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single stock transfer.
 *
 * @mixin \App\Domain\Inventory\Models\StockTransfer
 */
class StockTransferResource extends JsonResource
{
    /**
     * Transform the stock transfer into an array.
     *
     * Includes inline from/to warehouse objects and the items collection
     * when those relations are loaded.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'transfer_number' => $this->transfer_number,
            'status'          => $this->status,
            'reason'          => $this->reason,
            'requested_by'    => $this->requested_by,
            'approved_by'     => $this->approved_by,
            'dispatched_at'   => $this->dispatched_at?->toIso8601String(),
            'received_at'     => $this->received_at?->toIso8601String(),
            'notes'           => $this->notes,

            'from_warehouse' => $this->whenLoaded('fromWarehouse', fn () => $this->fromWarehouse ? [
                'id'   => $this->fromWarehouse->id,
                'code' => $this->fromWarehouse->code,
                'name' => $this->fromWarehouse->name,
            ] : null),

            'to_warehouse' => $this->whenLoaded('toWarehouse', fn () => $this->toWarehouse ? [
                'id'   => $this->toWarehouse->id,
                'code' => $this->toWarehouse->code,
                'name' => $this->toWarehouse->name,
            ] : null),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                  => $item->id,
                'product_variant_id'  => $item->product_variant_id,
                'quantity_requested'  => $item->quantity_requested,
                'quantity_dispatched' => $item->quantity_dispatched,
                'quantity_received'   => $item->quantity_received,
                'notes'               => $item->notes,
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
