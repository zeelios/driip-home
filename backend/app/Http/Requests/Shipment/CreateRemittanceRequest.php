<?php

declare(strict_types=1);

namespace App\Http\Requests\Shipment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new COD remittance record.
 */
class CreateRemittanceRequest extends FormRequest
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
     * Get the validation rules for creating a remittance record.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'courier_code'          => ['required', 'string', 'in:ghn,ghtk,spx,viettel'],
            'remittance_reference'  => ['nullable', 'string', 'max:100'],
            'period_from'           => ['required', 'date'],
            'period_to'             => ['required', 'date', 'after_or_equal:period_from'],
            'total_cod_collected'   => ['required', 'integer', 'min:0'],
            'total_fees_deducted'   => ['required', 'integer', 'min:0'],
            'net_remittance'        => ['required', 'integer'],
            'notes'                 => ['nullable', 'string'],
        ];
    }
}
