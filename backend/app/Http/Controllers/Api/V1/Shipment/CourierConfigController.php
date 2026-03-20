<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shipment;

use App\Domain\Shipment\Models\CourierConfig;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Shipment\UpdateCourierConfigRequest;
use App\Http\Resources\Shipment\CourierConfigResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Full CRUD management of courier integration configurations.
 *
 * Credentials (api_key, api_secret, webhook_secret) are accepted on write
 * but are never returned in read responses via CourierConfigResource.
 */
class CourierConfigController extends BaseApiController
{
    /**
     * List all courier configurations.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $configs = CourierConfig::orderBy('courier_code')->get();

        return CourierConfigResource::collection($configs);
    }

    /**
     * Create a new courier configuration.
     *
     * @param  Request  $request
     * @return CourierConfigResource|JsonResponse
     */
    public function store(Request $request): CourierConfigResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'courier_code'    => ['required', 'string', 'max:20', 'unique:courier_configs,courier_code'],
                'name'            => ['required', 'string', 'max:100'],
                'api_endpoint'    => ['nullable', 'string', 'max:500'],
                'api_key'         => ['nullable', 'string'],
                'api_secret'      => ['nullable', 'string'],
                'account_id'      => ['nullable', 'string', 'max:100'],
                'pickup_hub_code' => ['nullable', 'string', 'max:50'],
                'pickup_address'  => ['nullable', 'array'],
                'webhook_secret'  => ['nullable', 'string'],
                'is_active'       => ['boolean'],
                'settings'        => ['nullable', 'array'],
            ]);

            $config = CourierConfig::create($validated);

            return (new CourierConfigResource($config))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_COURIER_CONFIG');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_COURIER_CONFIG');
        }
    }

    /**
     * Show a single courier configuration.
     *
     * @param  CourierConfig  $courierConfig
     * @return CourierConfigResource
     */
    public function show(CourierConfig $courierConfig): CourierConfigResource
    {
        return new CourierConfigResource($courierConfig);
    }

    /**
     * Update an existing courier configuration.
     *
     * @param  UpdateCourierConfigRequest  $request
     * @param  CourierConfig               $courierConfig
     * @return CourierConfigResource|JsonResponse
     */
    public function update(UpdateCourierConfigRequest $request, CourierConfig $courierConfig): CourierConfigResource|JsonResponse
    {
        try {
            $courierConfig->update($request->validated());

            return new CourierConfigResource($courierConfig->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_COURIER_CONFIG');
        }
    }

    /**
     * Permanently delete a courier configuration (hard delete).
     *
     * @param  CourierConfig  $courierConfig
     * @return JsonResponse
     */
    public function destroy(CourierConfig $courierConfig): JsonResponse
    {
        try {
            $courierConfig->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_COURIER_CONFIG');
        }
    }
}
