<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tax;

use App\Domain\Tax\Actions\GenerateTaxInvoiceAction;
use App\Domain\Tax\Models\TaxInvoice;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Tax\TaxInvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages tax invoice records (list, show, generate, download).
 */
class TaxInvoiceController extends BaseApiController
{
    /**
     * List tax invoices with optional filters.
     *
     * Supports filtering by order_id, invoice_type, and issued_at date range
     * via query parameters: from_date and to_date (YYYY-MM-DD).
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = TaxInvoice::with('order')->latest('created_at');

            if ($request->filled('order_id')) {
                $query->where('order_id', $request->input('order_id'));
            }

            if ($request->filled('invoice_type')) {
                $query->where('invoice_type', $request->input('invoice_type'));
            }

            if ($request->filled('from_date')) {
                $query->whereDate('issued_at', '>=', $request->input('from_date'));
            }

            if ($request->filled('to_date')) {
                $query->whereDate('issued_at', '<=', $request->input('to_date'));
            }

            return TaxInvoiceResource::collection($query->paginate(20));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_TAX_INVOICES');
        }
    }

    /**
     * Show a single tax invoice.
     *
     * @param  TaxInvoice  $taxInvoice
     * @return TaxInvoiceResource|JsonResponse
     */
    public function show(TaxInvoice $taxInvoice): TaxInvoiceResource|JsonResponse
    {
        try {
            $taxInvoice->loadMissing('order');
            return new TaxInvoiceResource($taxInvoice);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_TAX_INVOICE');
        }
    }

    /**
     * Generate a new tax invoice for an order.
     *
     * @param  Request                  $request
     * @param  GenerateTaxInvoiceAction $action
     * @return TaxInvoiceResource|JsonResponse
     */
    public function store(Request $request, GenerateTaxInvoiceAction $action): TaxInvoiceResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id'      => ['required', 'string', 'max:36'],
                'invoice_type'  => ['sometimes', 'string', 'max:50'],
                'buyer_name'    => ['nullable', 'string', 'max:200'],
                'buyer_tax_code' => ['nullable', 'string', 'max:50'],
                'buyer_address' => ['nullable', 'string'],
            ]);

            $invoice = $action->execute(
                orderId:       $validated['order_id'],
                invoiceType:   $validated['invoice_type']   ?? 'vat',
                buyerName:     $validated['buyer_name']     ?? null,
                buyerTaxCode:  $validated['buyer_tax_code'] ?? null,
                buyerAddress:  $validated['buyer_address']  ?? null,
                createdBy:     $request->user()?->id,
            );

            return (new TaxInvoiceResource($invoice))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_TAX_INVOICE');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_TAX_INVOICE');
        }
    }

    /**
     * Redirect to the generated PDF file for a tax invoice.
     *
     * Returns 404 if the PDF has not yet been generated (file_url is null).
     *
     * @param  TaxInvoice  $taxInvoice
     * @return RedirectResponse|JsonResponse
     */
    public function download(TaxInvoice $taxInvoice): RedirectResponse|JsonResponse
    {
        try {
            if ($taxInvoice->file_url === null) {
                return $this->notFound('DOWNLOAD_TAX_INVOICE', 'PDF has not been generated yet for this invoice.');
            }

            return redirect()->away($taxInvoice->file_url);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DOWNLOAD_TAX_INVOICE');
        }
    }
}
