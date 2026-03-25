<?php

declare(strict_types=1);

namespace App\Http\Requests\Shipment;

use App\Domain\Shipment\Data\CreateShipmentDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for creating a new shipment.
 */
class CreateShipmentRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Permission checks are handled at the route/controller level.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a shipment.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'order_id'     => ['required', 'uuid', 'exists:orders,id'],
            'courier_code' => ['required', 'string', 'in:ghn,ghtk,spx,viettel'],
            'cod_amount'   => ['required', 'integer', 'min:0'],
            'weight_kg'    => ['nullable', 'numeric', 'min:0.001'],
        ];
    }

    /**
     * Build the DTO from the validated request data.
     *
     * @return CreateShipmentDto
     */
    public function dto(): CreateShipmentDto
    {
        return CreateShipmentDto::fromArray($this->validated());
    }
}
