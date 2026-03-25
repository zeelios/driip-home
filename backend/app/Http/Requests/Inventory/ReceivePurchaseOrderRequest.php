<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\ReceivePurchaseOrderDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for recording goods received against a purchase order.
 */
class ReceivePurchaseOrderRequest extends ApiRequest
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
     * Get the validation rules for receiving a purchase order.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'received_by'                    => ['required', 'uuid'],
            'notes'                          => ['nullable', 'string'],
            'receipt_items'                  => ['required', 'array'],
            'receipt_items.*.po_item_id'     => ['required', 'uuid'],
            'receipt_items.*.qty_received'   => ['required', 'integer', 'min:0'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): ReceivePurchaseOrderDto
    {
        return ReceivePurchaseOrderDto::fromArray($this->validated());
    }

}
