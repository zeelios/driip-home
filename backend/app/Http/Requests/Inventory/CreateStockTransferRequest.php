<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new stock transfer request.
 */
class CreateStockTransferRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a stock transfer.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'from_warehouse_id'                   => ['required', 'uuid'],
            'to_warehouse_id'                     => ['required', 'uuid', 'different:from_warehouse_id'],
            'reason'                              => ['nullable', 'string', 'max:500'],
            'notes'                               => ['nullable', 'string'],
            'items'                               => ['required', 'array', 'min:1'],
            'items.*.product_variant_id'          => ['required', 'uuid'],
            'items.*.quantity_requested'          => ['required', 'integer', 'min:1'],
            'items.*.notes'                       => ['nullable', 'string'],
        ];
    }

    /**
     * Build a CreateStockTransferDto from the validated request data.
     *
     * @return \App\Domain\Inventory\Data\CreateStockTransferDto
     */
    public function dto(): \App\Domain\Inventory\Data\CreateStockTransferDto
    {
        return new \App\Domain\Inventory\Data\CreateStockTransferDto(
            fromWarehouseId: $this->input('from_warehouse_id'),
            toWarehouseId:   $this->input('to_warehouse_id'),
            items:           $this->input('items', []),
            requestedBy:     $this->user()->id,
            reason:          $this->input('reason'),
            notes:           $this->input('notes'),
        );
    }
}
