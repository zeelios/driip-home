<?php

declare(strict_types=1);

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a TaxInvoice model into a JSON-serializable array.
 *
 * Conditionally includes the order number when the order relation is loaded.
 *
 * @mixin \App\Domain\Tax\Models\TaxInvoice
 */
class TaxInvoiceResource extends JsonResource
{
    /**
     * Transform the tax invoice into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_id'       => $this->order_id,
            'order_number'   => $this->whenLoaded(
                'order',
                fn () => $this->order?->order_number ?? null,
            ),
            'invoice_number' => $this->invoice_number,
            'invoice_type'   => $this->invoice_type,
            'buyer_name'     => $this->buyer_name,
            'buyer_tax_code' => $this->buyer_tax_code,
            'buyer_address'  => $this->buyer_address,
            'issued_at'      => $this->issued_at?->toIso8601String(),
            'file_url'       => $this->file_url,
            'created_by'     => $this->created_by,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
