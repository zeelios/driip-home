<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Domain\Settings\Models\Setting;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages application-wide configuration settings.
 *
 * Settings are grouped by namespace (loyalty, tax, shipping, etc.) and
 * returned as a nested object keyed by group name.
 */
class SettingsController extends BaseApiController
{
    /**
     * List all settings grouped by their group namespace.
     *
     * Returns an object where each key is a group name and the value is
     * a key-value map of settings within that group.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $settings = Setting::orderBy('group')->orderBy('key')->get();

            $grouped = $settings->groupBy('group')->map(function ($items) {
                return $items->mapWithKeys(fn ($s) => [$s->key => $s->value])->toArray();
            })->toArray();

            return response()->json(['success' => true, 'data' => $grouped]);
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
                'settings'              => ['required', 'array', 'min:1'],
                'settings.*.group'      => ['required', 'string', 'max:100'],
                'settings.*.key'        => ['required', 'string', 'max:100'],
                'settings.*.value'      => ['present'],
            ]);

            foreach ($validated['settings'] as $item) {
                Setting::set($item['group'], $item['key'], $item['value']);
            }

            return response()->json(['success' => true, 'message' => 'Settings updated.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_SETTINGS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_SETTINGS');
        }
    }
}
