<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Domain\Order\Actions\RecordCODCollectionAction;
use App\Domain\Order\Actions\RecordPaymentAction;
use App\Domain\Order\Data\RecordPaymentDto;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Services\OrderActivityLogger;
use App\Domain\Shared\Services\ImageUploadService;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Order payment and deposit management controller.
 */
class OrderPaymentController extends BaseApiController
{
    public function __construct(
        private readonly OrderActivityLogger $activityLogger,
        private readonly ImageUploadService $imageUpload
    ) {
    }

    /**
     * Record a deposit payment for an order.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse|OrderResource
     */
    public function recordDeposit(Request $request, Order $order): JsonResponse|OrderResource
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
            'proof_files' => 'nullable|array|max:5',
            'proof_files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            $amount = (int) $request->input('amount');
            $notes = $request->input('notes');

            // Upload proof images if provided
            $proofUrls = [];
            if ($request->hasFile('proof_files')) {
                foreach ($request->file('proof_files') as $file) {
                    $proofUrls[] = $this->imageUpload->uploadPaymentProof($file, $order->order_number);
                }
            }

            // Record the deposit
            $order->recordDeposit($amount, $proofUrls, $notes);

            // Log the activity
            $this->activityLogger->logDepositRecorded(
                $order,
                $amount,
                $order->balanceDue(),
                $proofUrls,
                $request->user()
            );

            return OrderResource::make($order->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'RECORD_DEPOSIT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'RECORD_DEPOSIT');
        }
    }

    /**
     * Upload additional payment proof images.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse
     */
    public function uploadProof(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proof_files' => 'required|array|min:1|max:5',
            'proof_files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            $newUrls = [];
            foreach ($request->file('proof_files') as $file) {
                $newUrls[] = $this->imageUpload->uploadPaymentProof($file, $order->order_number);
            }

            // Merge with existing URLs
            $allUrls = array_merge($order->deposit_proof_urls ?? [], $newUrls);

            $order->update(['deposit_proof_urls' => $allUrls]);

            // Log the upload
            $this->activityLogger->logFileUpload(
                $order,
                'payment_proof',
                implode(', ', $newUrls),
                $request->user()
            );

            return response()->json([
                'data' => [
                    'uploaded_urls' => $newUrls,
                    'all_urls' => $allUrls,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPLOAD_PROOF');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPLOAD_PROOF');
        }
    }

    /**
     * Remove a payment proof image by index.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @param  int      $index
     * @return JsonResponse
     */
    public function removeProof(Request $request, Order $order, int $index): JsonResponse
    {
        $urls = $order->deposit_proof_urls ?? [];

        if (!isset($urls[$index])) {
            return $this->notFound('REMOVE_PROOF', 'Proof image not found at that index');
        }

        try {
            // Optionally delete from storage
            $this->imageUpload->deleteIfExists($urls[$index]);

            // Remove from array
            unset($urls[$index]);
            $order->update(['deposit_proof_urls' => array_values($urls)]);

            return response()->json(['message' => 'Proof image removed']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'REMOVE_PROOF');
        }
    }

    /**
     * Mark an order as fully paid (verify payment).
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse|OrderResource
     */
    public function verifyPayment(Request $request, Order $order): JsonResponse|OrderResource
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|string|in:bank_transfer,momo,zalopay,vnpay,credit_card,cod,cash',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        try {
            $method = $request->input('method');
            $reference = $request->input('reference');
            $notes = $request->input('notes');

            $order->markFullyPaid($method, $reference);

            if ($notes) {
                $order->update([
                    'payment_notes' => ($order->payment_notes ? $order->payment_notes . "\n" : '') . $notes,
                ]);
            }

            // Log the payment
            $this->activityLogger->logPaymentReceived(
                $order,
                $order->total_after_tax,
                $method,
                $reference,
                $request->user()
            );

            return OrderResource::make($order->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'VERIFY_PAYMENT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'VERIFY_PAYMENT');
        }
    }

    /**
     * Record a generic payment for an order (deposit, final, refund, adjustment).
     *
     * @param  Request              $request
     * @param  Order                $order
     * @param  RecordPaymentAction  $action
     * @return JsonResponse|OrderResource
     */
    public function recordPayment(Request $request, Order $order, RecordPaymentAction $action): JsonResponse|OrderResource
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:cod,bank_transfer,momo,zalopay,vnpay,credit_card,cash,loyalty_points',
            'payment_type' => 'required|string|in:deposit,final,cod_collection,refund,adjustment',
            'reference' => 'nullable|string|max:255',
            'proof_files' => 'nullable|array|max:5',
            'proof_files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'RECORD_PAYMENT');
        }

        try {
            // Upload proof images if provided
            $proofUrls = [];
            if ($request->hasFile('proof_files')) {
                foreach ($request->file('proof_files') as $file) {
                    $proofUrls[] = $this->imageUpload->uploadPaymentProof($file, $order->order_number);
                }
            }

            $dto = new RecordPaymentDto(
                amount: (int) $request->input('amount'),
                paymentMethod: $request->input('payment_method'),
                paymentType: $request->input('payment_type'),
                reference: $request->input('reference'),
                proofUrls: $proofUrls,
                notes: $request->input('notes'),
                recordedBy: $request->user()?->id,
            );

            $payment = $action->execute($order, $dto);

            return response()->json([
                'data' => [
                    'payment' => $payment,
                    'order' => OrderResource::make($order->refresh()),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'RECORD_PAYMENT');
        }
    }

    /**
     * Record COD (Cash on Delivery) collection.
     *
     * @param  Request                      $request
     * @param  Order                        $order
     * @param  RecordCODCollectionAction  $action
     * @return JsonResponse|OrderResource
     */
    public function recordCodCollection(Request $request, Order $order, RecordCODCollectionAction $action): JsonResponse|OrderResource
    {
        $validator = Validator::make($request->all(), [
            'collected_amount' => 'required|integer|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'COD_COLLECTION');
        }

        try {
            $result = $action->execute(
                order: $order,
                collectedAmount: (int) $request->input('collected_amount'),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
                recordedBy: $request->user()?->id,
            );

            return response()->json([
                'data' => [
                    'payment' => $result['payment'],
                    'discrepancy' => $result['discrepancy'],
                    'status' => $result['status'],
                    'order' => OrderResource::make($order->refresh()),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'COD_COLLECTION');
        }
    }
}
