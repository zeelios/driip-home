<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Loyalty\Actions\CreateTierAction;
use App\Domain\Loyalty\Actions\UpdateTierAction;
use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Loyalty\CreateTierRequest;
use App\Http\Requests\Loyalty\UpdateTierRequest;
use App\Http\Resources\Loyalty\LoyaltyTierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages loyalty tier definitions — create, read, update, and delete.
 *
 * All write operations delegate to dedicated Action classes.
 */
class LoyaltyTierController extends BaseApiController
{
    /**
     * @param CreateTierAction $createTier Action responsible for creating a new loyalty tier.
     * @param UpdateTierAction $updateTier Action responsible for updating a loyalty tier.
     */
    public function __construct(
        private readonly CreateTierAction $createTier,
        private readonly UpdateTierAction $updateTier,
    ) {}

    /**
     * List all loyalty tiers ordered by sort_order.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $tiers = LoyaltyTier::orderBy('sort_order')->get();

        return LoyaltyTierResource::collection($tiers);
    }

    /**
     * Create a new loyalty tier.
     *
     * @param  CreateTierRequest  $request
     * @return LoyaltyTierResource|JsonResponse
     */
    public function store(CreateTierRequest $request): LoyaltyTierResource|JsonResponse
    {
        try {
            $tier = $this->createTier->execute($request->validated());

            return (new LoyaltyTierResource($tier))->response()->setStatusCode(201);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_LOYALTY_TIER');
        }
    }

    /**
     * Show a single loyalty tier.
     *
     * @param  LoyaltyTier  $loyaltyTier
     * @return LoyaltyTierResource
     */
    public function show(LoyaltyTier $tier): LoyaltyTierResource
    {
        return new LoyaltyTierResource($tier);
    }

    /**
     * Update an existing loyalty tier.
     *
     * @param  UpdateTierRequest  $request
     * @param  LoyaltyTier        $loyaltyTier
     * @return LoyaltyTierResource|JsonResponse
     */
    public function update(UpdateTierRequest $request, LoyaltyTier $tier): LoyaltyTierResource|JsonResponse
    {
        try {
            $tier = $this->updateTier->execute($tier, $request->validated());

            return new LoyaltyTierResource($tier);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_LOYALTY_TIER');
        }
    }

    /**
     * Delete a loyalty tier (hard delete).
     *
     * @param  LoyaltyTier  $loyaltyTier
     * @return JsonResponse
     */
    public function destroy(LoyaltyTier $tier): JsonResponse
    {
        try {
            $tier->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_LOYALTY_TIER');
        }
    }
}
