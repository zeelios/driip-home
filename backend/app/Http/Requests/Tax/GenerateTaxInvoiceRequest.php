<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use App\Domain\Tax\Data\GenerateTaxInvoiceDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for generating a tax invoice for an order.
 *
 * Supports both retail (B2C) and VAT (B2B) invoice types. For B2B invoices
 * the buyer_tax_code should be provided.
 */
class GenerateTaxInvoiceRequest extends FormRequest
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
     * Get the validation rules for generating a tax invoice.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'order_id'       => ['required', 'uuid'],
            'invoice_type'   => ['nullable', 'in:retail,vat'],
            'buyer_name'     => ['nullable', 'string', 'max:255'],
            'buyer_tax_code' => ['nullable', 'string', 'max:20'],
            'buyer_address'  => ['nullable', 'string'],
        ];
    }

    /**
     * Build a GenerateTaxInvoiceDto from the validated request data.
     *
     * @return GenerateTaxInvoiceDto
     */
    public function dto(): GenerateTaxInvoiceDto
    {
        return GenerateTaxInvoiceDto::fromArray($this->validated());
    }
}
