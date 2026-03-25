<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Domain\Order\Models\Order;
use App\Domain\Payment\Models\BankCheckLog;
use App\Domain\Payment\Models\PendingDeposit;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Pending deposit management controller.
 */
class PendingDepositController extends BaseApiController
{
    /**
     * List all pending deposits.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:pending,matched,expired,cancelled',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'LIST_PENDING_DEPOSITS');
        }

        try {
            $query = PendingDeposit::with(['order', 'bankConfig']);

            if ($request->input('status')) {
                $query->where('status', $request->input('status'));
            }

            $deposits = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'data' => $deposits->through(fn ($deposit) => [
                    'id' => $deposit->id,
                    'order' => [
                        'id' => $deposit->order->id,
                        'order_number' => $deposit->order->order_number,
                        'customer_name' => $deposit->order->customer?->fullName() ?? $deposit->order->guest_name,
                    ],
                    'expected_amount' => $deposit->expected_amount,
                    'amount_tolerance' => $deposit->amount_tolerance,
                    'transfer_content_pattern' => $deposit->transfer_content_pattern,
                    'bank_config' => $deposit->bankConfig?->only(['id', 'bank_provider', 'account_number']),
                    'status' => $deposit->status,
                    'expires_at' => $deposit->expires_at->toIso8601String(),
                    'is_expired' => $deposit->isExpired(),
                    'matched_transaction_id' => $deposit->matched_transaction_id,
                    'matched_at' => $deposit->matched_at?->toIso8601String(),
                    'created_at' => $deposit->created_at?->toIso8601String(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_PENDING_DEPOSITS');
        }
    }

    /**
     * Create a pending deposit for an order.
     *
     * @param  Request  $request
     * @param  Order    $order
     * @return JsonResponse
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'expected_amount' => 'required|integer|min:1',
            'amount_tolerance' => 'nullable|integer|min:0',
            'transfer_content_pattern' => 'nullable|string|max:255',
            'bank_config_id' => 'nullable|uuid|exists:bank_configs,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:168',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'CREATE_PENDING_DEPOSIT');
        }

        try {
            // Generate transfer content pattern if not provided
            $pattern = $request->input('transfer_content_pattern') ?? $order->order_number;

            $deposit = PendingDeposit::create([
                'order_id' => $order->id,
                'expected_amount' => $request->input('expected_amount'),
                'amount_tolerance' => $request->input('amount_tolerance', 0),
                'transfer_content_pattern' => $pattern,
                'bank_config_id' => $request->input('bank_config_id'),
                'status' => 'pending',
                'expires_at' => now()->addHours($request->input('expires_in_hours', 24)),
            ]);

            return response()->json([
                'data' => [
                    'id' => $deposit->id,
                    'order_id' => $deposit->order_id,
                    'expected_amount' => $deposit->expected_amount,
                    'transfer_content_pattern' => $deposit->transfer_content_pattern,
                    'status' => $deposit->status,
                    'expires_at' => $deposit->expires_at->toIso8601String(),
                ],
                'message' => 'Pending deposit created successfully',
            ], 201);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_PENDING_DEPOSIT');
        }
    }

    /**
     * Show a specific pending deposit.
     *
     * @param  PendingDeposit  $pendingDeposit
     * @return JsonResponse
     */
    public function show(PendingDeposit $pendingDeposit): JsonResponse
    {
        try {
            $pendingDeposit->load(['order', 'bankConfig', 'matchedBy']);

            return response()->json([
                'data' => [
                    'id' => $pendingDeposit->id,
                    'order' => [
                        'id' => $pendingDeposit->order->id,
                        'order_number' => $pendingDeposit->order->order_number,
                        'customer' => $pendingDeposit->order->customer?->only(['id', 'full_name', 'phone']),
                        'guest_name' => $pendingDeposit->order->guest_name,
                        'guest_phone' => $pendingDeposit->order->guest_phone,
                    ],
                    'expected_amount' => $pendingDeposit->expected_amount,
                    'amount_tolerance' => $pendingDeposit->amount_tolerance,
                    'acceptable_range' => $pendingDeposit->getAcceptableAmountRange(),
                    'transfer_content_pattern' => $pendingDeposit->transfer_content_pattern,
                    'bank_config' => $pendingDeposit->bankConfig?->only(['id', 'bank_provider', 'account_number', 'account_name']),
                    'status' => $pendingDeposit->status,
                    'expires_at' => $pendingDeposit->expires_at->toIso8601String(),
                    'is_expired' => $pendingDeposit->isExpired(),
                    'can_be_matched' => $pendingDeposit->canBeMatched(),
                    'matched_transaction_id' => $pendingDeposit->matched_transaction_id,
                    'matched_at' => $pendingDeposit->matched_at?->toIso8601String(),
                    'matched_by' => $pendingDeposit->matchedBy?->only(['id', 'name']),
                    'created_at' => $pendingDeposit->created_at?->toIso8601String(),
                    'updated_at' => $pendingDeposit->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_PENDING_DEPOSIT');
        }
    }

    /**
     * Cancel a pending deposit.
     *
     * @param  PendingDeposit  $pendingDeposit
     * @return JsonResponse
     */
    public function cancel(PendingDeposit $pendingDeposit): JsonResponse
    {
        try {
            if ($pendingDeposit->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending deposits can be cancelled',
                ], 422);
            }

            $pendingDeposit->markCancelled();

            return response()->json([
                'data' => [
                    'id' => $pendingDeposit->id,
                    'status' => $pendingDeposit->status,
                ],
                'message' => 'Pending deposit cancelled successfully',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CANCEL_PENDING_DEPOSIT');
        }
    }

    /**
     * Manually match a pending deposit to a transaction.
     *
     * @param  Request         $request
     * @param  PendingDeposit  $pendingDeposit
     * @return JsonResponse
     */
    public function manualMatch(Request $request, PendingDeposit $pendingDeposit): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'MANUAL_MATCH_DEPOSIT');
        }

        try {
            if (!$pendingDeposit->canBeMatched()) {
                return response()->json([
                    'message' => 'This deposit cannot be matched (expired or already matched)',
                ], 422);
            }

            // Validate amount is within acceptable range
            if (!$pendingDeposit->isAmountAcceptable($request->input('amount'))) {
                $range = $pendingDeposit->getAcceptableAmountRange();

                return response()->json([
                    'message' => "Amount is outside acceptable range ({$range['min']} - {$range['max']})",
                ], 422);
            }

            $pendingDeposit->markMatched(
                $request->input('transaction_id'),
                $request->user()?->id
            );

            // Record the payment
            $dto = new \App\Domain\Order\Data\RecordPaymentDto(
                amount: $request->input('amount'),
                paymentMethod: 'bank_transfer',
                paymentType: $request->input('amount') >= $pendingDeposit->order->total_after_tax ? 'final' : 'deposit',
                reference: $request->input('transaction_id'),
                notes: 'Manually matched by staff',
                recordedBy: $request->user()?->id,
            );

            $recordPayment = app(\App\Domain\Order\Actions\RecordPaymentAction::class);
            $payment = $recordPayment->execute($pendingDeposit->order, $dto);

            return response()->json([
                'data' => [
                    'deposit' => [
                        'id' => $pendingDeposit->id,
                        'status' => $pendingDeposit->status,
                        'matched_transaction_id' => $pendingDeposit->matched_transaction_id,
                        'matched_at' => $pendingDeposit->matched_at?->toIso8601String(),
                    ],
                    'payment' => $payment,
                    'order' => OrderResource::make($pendingDeposit->order->refresh()),
                ],
                'message' => 'Deposit matched and payment recorded successfully',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'MANUAL_MATCH_DEPOSIT');
        }
    }

    /**
     * List bank check logs.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bank_config_id' => 'nullable|uuid|exists:bank_configs,id',
            'status' => 'nullable|string|in:success,failed,partial',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'LIST_BANK_LOGS');
        }

        try {
            $query = BankCheckLog::with('bankConfig');

            if ($request->input('bank_config_id')) {
                $query->where('bank_config_id', $request->input('bank_config_id'));
            }

            if ($request->input('status')) {
                $query->where('status', $request->input('status'));
            }

            $logs = $query->orderBy('started_at', 'desc')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'data' => $logs->through(fn ($log) => [
                    'id' => $log->id,
                    'bank_config' => $log->bankConfig?->only(['id', 'bank_provider', 'account_number']),
                    'status' => $log->status,
                    'transactions_found' => $log->transactions_found,
                    'deposits_matched' => $log->deposits_matched,
                    'error_message' => $log->error_message,
                    'duration_ms' => $log->duration_ms,
                    'started_at' => $log->started_at->toIso8601String(),
                    'completed_at' => $log->completed_at?->toIso8601String(),
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_BANK_LOGS');
        }
    }
}
