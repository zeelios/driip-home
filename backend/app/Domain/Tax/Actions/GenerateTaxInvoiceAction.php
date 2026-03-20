<?php

declare(strict_types=1);

namespace App\Domain\Tax\Actions;

use App\Domain\Settings\Models\Setting;
use App\Domain\Tax\Models\TaxInvoice;

/**
 * Action responsible for generating a tax invoice record for a given order.
 *
 * Produces a unique invoice number in the format {PREFIX}-{YYYYMM}-{SEQUENCE},
 * e.g. INV-202603-0042. PDF generation is deferred and handled by a
 * background job; file_url will be null until that job completes.
 */
class GenerateTaxInvoiceAction
{
    /**
     * Create a TaxInvoice record for the given order.
     *
     * @param  string      $orderId       UUID of the order being invoiced.
     * @param  string      $invoiceType   Type code (e.g. "vat", "retail").
     * @param  string|null $buyerName     Optional buyer name for B2B invoices.
     * @param  string|null $buyerTaxCode  Optional buyer tax identification code.
     * @param  string|null $buyerAddress  Optional buyer address.
     * @param  string|null $createdBy     UUID of the staff member generating the invoice.
     * @return TaxInvoice                 The newly created invoice record.
     */
    public function execute(
        string  $orderId,
        string  $invoiceType   = 'vat',
        ?string $buyerName     = null,
        ?string $buyerTaxCode  = null,
        ?string $buyerAddress  = null,
        ?string $createdBy     = null,
    ): TaxInvoice {
        $prefix   = (string) Setting::get('invoice', 'invoice_number_prefix', 'INV');
        $yearMonth = now()->format('Ym');
        $sequence  = $this->nextSequence($prefix, $yearMonth);

        $invoiceNumber = $prefix . '-' . $yearMonth . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        /** @var TaxInvoice $invoice */
        $invoice = TaxInvoice::create([
            'order_id'       => $orderId,
            'invoice_number' => $invoiceNumber,
            'invoice_type'   => $invoiceType,
            'buyer_name'     => $buyerName,
            'buyer_tax_code' => $buyerTaxCode,
            'buyer_address'  => $buyerAddress,
            'issued_at'      => now(),
            'file_url'       => null,
            'created_by'     => $createdBy,
            'created_at'     => now(),
        ]);

        return $invoice;
    }

    /**
     * Calculate the next sequential invoice number for this prefix and period.
     *
     * Counts existing invoices whose number starts with "{prefix}-{yearMonth}-"
     * and adds one to produce the next sequence value.
     *
     * @param  string  $prefix     The configured invoice prefix (e.g. "INV").
     * @param  string  $yearMonth  The current year-month string (e.g. "202603").
     * @return int                 The next sequence number (1-based).
     */
    private function nextSequence(string $prefix, string $yearMonth): int
    {
        $likePattern = $prefix . '-' . $yearMonth . '-%';

        return TaxInvoice::where('invoice_number', 'like', $likePattern)->count() + 1;
    }
}
