<?php

declare(strict_types=1);

namespace App\Domain\Tax\Data;

/**
 * Data Transfer Object for generating a tax invoice for an order.
 *
 * Supports both B2C (no tax code) and B2B (with buyer tax code) flows.
 * The order must already exist; no order creation is performed here.
 */
readonly class GenerateTaxInvoiceDto
{
    /**
     * Create a new GenerateTaxInvoiceDto.
     *
     * @param  string       $orderId       UUID of the order being invoiced.
     * @param  string       $invoiceType   Invoice type: 'retail' or 'vat'.
     * @param  string|null  $buyerName     Optional buyer name for B2B invoices.
     * @param  string|null  $buyerTaxCode  Optional VAT tax identification code of the buyer.
     * @param  string|null  $buyerAddress  Optional buyer billing address.
     */
    public function __construct(
        public string  $orderId,
        public string  $invoiceType,
        public ?string $buyerName,
        public ?string $buyerTaxCode,
        public ?string $buyerAddress,
    ) {}

    /**
     * Build from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId:      (string) $data['order_id'],
            invoiceType:  (string) ($data['invoice_type'] ?? 'retail'),
            buyerName:    isset($data['buyer_name']) ? (string) $data['buyer_name'] : null,
            buyerTaxCode: isset($data['buyer_tax_code']) ? (string) $data['buyer_tax_code'] : null,
            buyerAddress: isset($data['buyer_address']) ? (string) $data['buyer_address'] : null,
        );
    }
}
