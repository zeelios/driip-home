<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single supplier.
 *
 * @mixin \App\Domain\Inventory\Models\Supplier
 */
class SupplierResource extends JsonResource
{
    /**
     * Transform the supplier into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'contact_name'  => $this->contact_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'address'       => $this->address,
            'province'      => $this->province,
            'country'       => $this->country,
            'payment_terms' => $this->payment_terms,
            'notes'         => $this->notes,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
