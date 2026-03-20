<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Loyalty\LoyaltyTierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages loyalty tier definitions (create, read, update, delete).
 */
class LoyaltyTierController extends BaseApiController
{
    /**
     * List all loyalty tiers ordered by sort_order.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(): AnonymousResourceCollection|JsonResponse
    {
        try {
            $tiers = LoyaltyTier::orderBy('sort_order')->get();
            return LoyaltyTierResource::collection($tiers);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_LOYALTY_TIERS');
        }
    }

    /**
     * Create a new loyalty tier.
     *
     * @param  Request  $request
     * @return LoyaltyTierResource|JsonResponse
     */
    public function store(Request $request): LoyaltyTierResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'                => ['required', 'string', 'max:100'],
                'slug'                => ['required', 'string', 'max:100', 'unique:loyalty_tiers,slug'],
                'min_lifetime_points' => ['required', 'integer', 'min:0'],
                'discount_percent'    => ['required', 'numeric', 'min:0', 'max:100'],
                'free_shipping'       => ['boolean'],
                'early_access'        => ['boolean'],
                'birthday_multiplier' => ['numeric', 'min:1'],
                'perks'               => ['nullable', 'array'],
                'color'               => ['nullable', 'string', 'max:20'],
                'sort_order'          => ['integer', 'min:0'],
            ]);

            $tier = LoyaltyTier::create($validated);
            return (new LoyaltyTierResource($tier))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_LOYALTY_TIER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_LOYALTY_TIER');
        }
    }

    /**
     * Show a single loyalty tier.
     *
     * @param  LoyaltyTier  $loyaltyTier
     * @return LoyaltyTierResource|JsonResponse
     */
    public function show(LoyaltyTier $loyaltyTier): LoyaltyTierResource|JsonResponse
    {
        try {
            return new LoyaltyTierResource($loyaltyTier);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_LOYALTY_TIER');
        }
    }

    /**
     * Update an existing loyalty tier.
     *
     * @param  Request      $request
     * @param  LoyaltyTier  $loyaltyTier
     * @return LoyaltyTierResource|JsonResponse
     */
    public function update(Request $request, LoyaltyTier $loyaltyTier): LoyaltyTierResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'                => ['sometimes', 'string', 'max:100'],
                'slug'                => ['sometimes', 'string', 'max:100', 'unique:loyalty_tiers,slug,' . $loyaltyTier->id],
                'min_lifetime_points' => ['sometimes', 'integer', 'min:0'],
                'discount_percent'    => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'free_shipping'       => ['sometimes', 'boolean'],
                'early_access'        => ['sometimes', 'boolean'],
                'birthday_multiplier' => ['sometimes', 'numeric', 'min:1'],
                'perks'               => ['sometimes', 'nullable', 'array'],
                'color'               => ['sometimes', 'nullable', 'string', 'max:20'],
                'sort_order'          => ['sometimes', 'integer', 'min:0'],
            ]);

            $loyaltyTier->update($validated);
            return new LoyaltyTierResource($loyaltyTier->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_LOYALTY_TIER');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_LOYALTY_TIER');
        }
    }

    /**
     * Delete a loyalty tier.
     *
     * @param  LoyaltyTier  $loyaltyTier
     * @return JsonResponse
     */
    public function destroy(LoyaltyTier $loyaltyTier): JsonResponse
    {
        try {
            $loyaltyTier->delete();
            return response()->json(['success' => true], 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_LOYALTY_TIER');
        }
    }
}
