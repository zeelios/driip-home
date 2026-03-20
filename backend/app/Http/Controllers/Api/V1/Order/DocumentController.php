<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Models\Order;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Generate order-related documents such as packing slips and tax invoices.
 *
 * PDF generation uses Spatie Laravel PDF. Tax invoice creation delegates
 * to the Tax domain's GenerateTaxInvoiceAction.
 */
class DocumentController extends BaseApiController
{
    /**
     * Stream a PDF packing slip for the given order.
     *
     * Loads the order with its items and variant data, renders the
     * packing slip PDF template, and streams it to the client.
     *
     * @param  Order  $order
     * @return Response|JsonResponse
     */
    public function packingSlip(Order $order): Response|JsonResponse
    {
        try {
            $order->load(['items.variant', 'customer']);

            $pdf = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.packing-slip', ['order' => $order])
                ->format('a4');

            return response($pdf->base64(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"packing-slip-{$order->order_number}.pdf\"",
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'GENERATE_PACKING_SLIP');
        }
    }

    /**
     * Generate a VAT tax invoice for the given order.
     *
     * Accepts buyer information in the request body, delegates to the
     * Tax domain action, and returns the created TaxInvoice resource.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse
     */
    public function generateTaxInvoice(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'invoice_type'    => ['required', 'string'],
                'buyer_name'      => ['required', 'string'],
                'buyer_tax_code'  => ['nullable', 'string'],
                'buyer_address'   => ['nullable', 'string'],
            ]);

            $action  = app(\App\Domain\Tax\Actions\GenerateTaxInvoiceAction::class);
            $invoice = $action->execute($order, $validated);

            return response()->json(\App\Http\Resources\Tax\TaxInvoiceResource::make($invoice));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'GENERATE_TAX_INVOICE');
        }
    }
}
