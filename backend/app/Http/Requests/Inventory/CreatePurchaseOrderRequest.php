<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\CreatePurchaseOrderDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new purchase order.
 */
class CreatePurchaseOrderRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller / policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a purchase order.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id'                    => ['required', 'uuid'],
            'warehouse_id'                   => ['required', 'uuid'],
            'expected_arrival_at'            => ['nullable', 'date'],
            'notes'                          => ['nullable', 'string'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.product_variant_id'     => ['required', 'uuid'],
            'items.*.quantity_ordered'       => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'              => ['required', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CreatePurchaseOrderDto
    {
        return CreatePurchaseOrderDto::fromArray($this->validated());
    }

}
