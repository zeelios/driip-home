<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Domain\Settings\Models\Setting;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Settings\SettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

/**
 * Manages application-wide configuration settings.
 *
 * Settings are grouped by namespace (loyalty, tax, shipping, etc.) and
 * returned as a nested object keyed by group name.
 */
class SettingsController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings.view', only: ['index']),
            new Middleware('permission:settings.update', only: ['update']),
        ];
    }

    /**
     * List all settings as a flat array with full metadata.
     *
     * Returns array of {group, key, value, type, label} objects.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $settings = Setting::query()->orderBy('group')->orderBy('key')->get();

            return response()->json([
                'success' => true,
                'data' => SettingResource::collection($settings),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_SETTINGS');
        }
    }

    /**
     * Batch-update one or more settings.
     *
     * Accepts an array of {group, key, value} objects. Each entry is validated
     * and applied individually using Setting::set().
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'settings' => ['required', 'array', 'min:1'],
                'settings.*.group' => ['required', 'string', 'max:100'],
                'settings.*.key' => ['required', 'string', 'max:100'],
                'settings.*.value' => ['present'],
            ]);

            foreach ($validated['settings'] as $item) {
                Setting::set($item['group'], $item['key'], $item['value']);
            }

            return response()->json(['success' => true, 'message' => 'Settings updated.']);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'UPDATE_SETTINGS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_SETTINGS');
        }
    }
}
