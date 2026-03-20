<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tax;

use App\Domain\Tax\Models\TaxConfig;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages tax rate configurations (CRUD).
 */
class TaxConfigController extends BaseApiController
{
    /**
     * List all tax configurations, newest first.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(): AnonymousResourceCollection|JsonResponse
    {
        try {
            $configs = TaxConfig::orderByDesc('effective_from')->get();
            return JsonResource::collection($configs);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_TAX_CONFIGS');
        }
    }

    /**
     * Create a new tax rate configuration.
     *
     * @param  Request  $request
     * @return JsonResource|JsonResponse
     */
    public function store(Request $request): JsonResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'           => ['required', 'string', 'max:200'],
                'rate'           => ['required', 'numeric', 'min:0', 'max:100'],
                'applies_to'     => ['nullable', 'string', 'max:100'],
                'applies_to_ids' => ['nullable', 'array'],
                'effective_from' => ['required', 'date'],
                'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
                'is_active'      => ['boolean'],
            ]);

            $config = TaxConfig::create([
                ...$validated,
                'created_at' => now(),
            ]);

            return (new JsonResource($config))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_TAX_CONFIG');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_TAX_CONFIG');
        }
    }

    /**
     * Show a single tax configuration.
     *
     * @param  TaxConfig  $config
     * @return JsonResource|JsonResponse
     */
    public function show(TaxConfig $config): JsonResource|JsonResponse
    {
        try {
            return new JsonResource($config);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_TAX_CONFIG');
        }
    }

    /**
     * Update an existing tax configuration.
     *
     * @param  Request    $request
     * @param  TaxConfig  $config
     * @return JsonResource|JsonResponse
     */
    public function update(Request $request, TaxConfig $config): JsonResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'           => ['sometimes', 'string', 'max:200'],
                'rate'           => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'applies_to'     => ['sometimes', 'nullable', 'string', 'max:100'],
                'applies_to_ids' => ['sometimes', 'nullable', 'array'],
                'effective_from' => ['sometimes', 'date'],
                'effective_to'   => ['sometimes', 'nullable', 'date'],
                'is_active'      => ['sometimes', 'boolean'],
            ]);

            $config->update($validated);
            return new JsonResource($config->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_TAX_CONFIG');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_TAX_CONFIG');
        }
    }

    /**
     * Delete a tax configuration.
     *
     * @param  TaxConfig  $config
     * @return JsonResponse
     */
    public function destroy(TaxConfig $config): JsonResponse
    {
        try {
            $config->delete();
            return response()->json(['success' => true], 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_TAX_CONFIG');
        }
    }
}
