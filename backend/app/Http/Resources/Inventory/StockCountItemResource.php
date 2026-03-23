<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for StockCountItem.
 *
 * @mixin \App\Domain\Inventory\Models\StockCountItem
 */
class StockCountItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_count_id' => $this->stock_count_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity_expected' => $this->quantity_expected,
            'quantity_counted' => $this->quantity_counted,
            'variance' => $this->variance,
            'variance_value' => $this->variance_value,
            'notes' => $this->notes,
            'counted_by' => $this->counted_by,
            'counted_at' => $this->counted_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
