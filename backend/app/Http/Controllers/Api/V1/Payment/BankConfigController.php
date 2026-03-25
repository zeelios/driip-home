<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Domain\Payment\Models\BankConfig;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bank configuration management controller.
 */
class BankConfigController extends BaseApiController
{
    /**
     * List all bank configurations.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $configs = BankConfig::all();

            return response()->json([
                'data' => $configs->map(fn ($config) => [
                    'id' => $config->id,
                    'bank_provider' => $config->bank_provider,
                    'account_number' => $config->account_number,
                    'account_name' => $config->account_name,
                    'is_active' => $config->is_active,
                    'last_check_at' => $config->last_check_at?->toIso8601String(),
                    'check_interval_minutes' => $config->check_interval_minutes,
                    'is_due_for_check' => $config->isDueForCheck(),
                    'created_at' => $config->created_at?->toIso8601String(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_BANK_CONFIGS');
        }
    }

    /**
     * Store a new bank configuration.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bank_provider' => 'required|string|in:vietcombank,acb,techcombank,bidv,vietinbank,mbbank,sacombank,vpbank,tpbank,hdbank',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'credentials' => 'required|array',
            'credentials.username' => 'required|string',
            'credentials.password' => 'required|string',
            'check_interval_minutes' => 'nullable|integer|min:5|max:1440',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'CREATE_BANK_CONFIG');
        }

        try {
            $config = new BankConfig([
                'bank_provider' => $request->input('bank_provider'),
                'account_number' => $request->input('account_number'),
                'account_name' => $request->input('account_name'),
                'is_active' => $request->input('is_active', true),
                'check_interval_minutes' => $request->input('check_interval_minutes', 15),
            ]);

            $config->setCredentials($request->input('credentials'));
            $config->save();

            return response()->json([
                'data' => [
                    'id' => $config->id,
                    'bank_provider' => $config->bank_provider,
                    'account_number' => $config->account_number,
                    'account_name' => $config->account_name,
                    'is_active' => $config->is_active,
                    'check_interval_minutes' => $config->check_interval_minutes,
                ],
                'message' => 'Bank configuration created successfully',
            ], 201);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_BANK_CONFIG');
        }
    }

    /**
     * Show a specific bank configuration.
     *
     * @param  BankConfig  $bankConfig
     * @return JsonResponse
     */
    public function show(BankConfig $bankConfig): JsonResponse
    {
        try {
            return response()->json([
                'data' => [
                    'id' => $bankConfig->id,
                    'bank_provider' => $bankConfig->bank_provider,
                    'account_number' => $bankConfig->account_number,
                    'account_name' => $bankConfig->account_name,
                    'is_active' => $bankConfig->is_active,
                    'last_check_at' => $bankConfig->last_check_at?->toIso8601String(),
                    'check_interval_minutes' => $bankConfig->check_interval_minutes,
                    'is_due_for_check' => $bankConfig->isDueForCheck(),
                    'created_at' => $bankConfig->created_at?->toIso8601String(),
                    'updated_at' => $bankConfig->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_BANK_CONFIG');
        }
    }

    /**
     * Update a bank configuration.
     *
     * @param  Request     $request
     * @param  BankConfig  $bankConfig
     * @return JsonResponse
     */
    public function update(Request $request, BankConfig $bankConfig): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_name' => 'nullable|string|max:100',
            'credentials' => 'nullable|array',
            'credentials.username' => 'required_with:credentials|string',
            'credentials.password' => 'required_with:credentials|string',
            'is_active' => 'nullable|boolean',
            'check_interval_minutes' => 'nullable|integer|min:5|max:1440',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->toException(), 'UPDATE_BANK_CONFIG');
        }

        try {
            $updateData = [];

            if ($request->has('account_name')) {
                $updateData['account_name'] = $request->input('account_name');
            }

            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->input('is_active');
            }

            if ($request->has('check_interval_minutes')) {
                $updateData['check_interval_minutes'] = $request->input('check_interval_minutes');
            }

            $bankConfig->update($updateData);

            if ($request->has('credentials')) {
                $bankConfig->setCredentials($request->input('credentials'));
                $bankConfig->save();
            }

            return response()->json([
                'data' => [
                    'id' => $bankConfig->id,
                    'bank_provider' => $bankConfig->bank_provider,
                    'account_number' => $bankConfig->account_number,
                    'account_name' => $bankConfig->account_name,
                    'is_active' => $bankConfig->is_active,
                    'check_interval_minutes' => $bankConfig->check_interval_minutes,
                ],
                'message' => 'Bank configuration updated successfully',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_BANK_CONFIG');
        }
    }

    /**
     * Delete a bank configuration.
     *
     * @param  BankConfig  $bankConfig
     * @return JsonResponse
     */
    public function destroy(BankConfig $bankConfig): JsonResponse
    {
        try {
            $bankConfig->delete();

            return response()->json([
                'message' => 'Bank configuration deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_BANK_CONFIG');
        }
    }

    /**
     * Test the bank connection.
     *
     * @param  BankConfig  $bankConfig
     * @return JsonResponse
     */
    public function testConnection(BankConfig $bankConfig): JsonResponse
    {
        try {
            // This would test the connection by attempting a login
            // For now, return a placeholder response

            return response()->json([
                'message' => 'Connection test not yet implemented - requires RPA service',
                'bank_provider' => $bankConfig->bank_provider,
                'account_number' => $bankConfig->account_number,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'TEST_BANK_CONNECTION');
        }
    }

    /**
     * Trigger a manual bank check.
     *
     * @param  BankConfig  $bankConfig
     * @return JsonResponse
     */
    public function triggerCheck(BankConfig $bankConfig): JsonResponse
    {
        try {
            // Dispatch the check job immediately for this bank
            \App\Jobs\CheckBankTransactionsJob::dispatch($bankConfig);

            return response()->json([
                'message' => 'Bank check triggered',
                'bank_config_id' => $bankConfig->id,
                'bank_provider' => $bankConfig->bank_provider,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'TRIGGER_BANK_CHECK');
        }
    }
}
