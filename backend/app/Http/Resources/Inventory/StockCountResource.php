<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single stock count task.
 *
 * @mixin \App\Domain\Inventory\Models\StockCount
 */
class StockCountResource extends JsonResource
{
    /**
     * Transform the stock count into an array.
     *
     * Includes the warehouse inline and the items collection (with variance info)
     * when those relations are loaded.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'count_number'          => $this->count_number,
            'type'                  => $this->type,
            'status'                => $this->status,
            'scheduled_at'          => $this->scheduled_at?->toDateString(),
            'started_at'            => $this->started_at?->toIso8601String(),
            'completed_at'          => $this->completed_at?->toIso8601String(),
            'approved_by'           => $this->approved_by,
            'approved_at'           => $this->approved_at?->toIso8601String(),
            'total_variance_qty'    => $this->total_variance_qty,
            'total_variance_value'  => $this->total_variance_value,
            'notes'                 => $this->notes,
            'created_by'            => $this->created_by,

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                  => $item->id,
                'product_variant_id'  => $item->product_variant_id,
                'quantity_expected'   => $item->quantity_expected,
                'quantity_counted'    => $item->quantity_counted,
                'variance'            => $item->variance,
                'variance_value'      => $item->variance_value,
                'notes'               => $item->notes,
                'counted_by'          => $item->counted_by,
                'counted_at'          => $item->counted_at?->toIso8601String(),
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
