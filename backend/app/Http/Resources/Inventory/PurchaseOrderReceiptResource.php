<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for PurchaseOrderReceipt.
 *
 * @mixin \App\Domain\Inventory\Models\PurchaseOrderReceipt
 */
class PurchaseOrderReceiptResource extends JsonResource
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
            'purchase_order_id' => $this->purchase_order_id,
            'receipt_number' => $this->receipt_number,
            'received_by' => $this->received_by,
            'received_at' => $this->received_at,
            'notes' => $this->notes,
            'attachments' => $this->attachments,
            'receipt_items' => $this->receipt_items,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
