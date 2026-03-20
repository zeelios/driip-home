<?php

declare(strict_types=1);

namespace App\Http\Requests\Shipment;

use App\Domain\Shipment\Data\ReconcileRemittanceDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for reconciling a COD remittance.
 */
class ReconcileRemittanceRequest extends FormRequest
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
     * Get the validation rules for reconciling a remittance.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.tracking_number'   => ['required', 'string', 'max:100'],
            'items.*.cod_amount'        => ['required', 'integer', 'min:0'],
            'items.*.shipping_fee'      => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Build the DTO from the validated request data.
     *
     * @param  string  $remittanceId  UUID of the target remittance batch.
     * @return ReconcileRemittanceDto
     */
    public function dto(string $remittanceId): ReconcileRemittanceDto
    {
        return ReconcileRemittanceDto::fromArray($remittanceId, $this->validated());
    }
}
