<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Loyalty\Models\LoyaltyCampaign;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages loyalty campaign definitions (CRUD).
 */
class LoyaltyCampaignController extends BaseApiController
{
    /**
     * List all loyalty campaigns, newest first.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(): AnonymousResourceCollection|JsonResponse
    {
        try {
            $campaigns = LoyaltyCampaign::latest()->paginate(20);
            return JsonResource::collection($campaigns);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_LOYALTY_CAMPAIGNS');
        }
    }

    /**
     * Create a new loyalty campaign.
     *
     * @param  Request  $request
     * @return JsonResource|JsonResponse
     */
    public function store(Request $request): JsonResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'         => ['required', 'string', 'max:200'],
                'type'         => ['required', 'string', 'max:50'],
                'multiplier'   => ['nullable', 'numeric', 'min:1'],
                'bonus_points' => ['nullable', 'integer', 'min:0'],
                'conditions'   => ['nullable', 'array'],
                'starts_at'    => ['nullable', 'date'],
                'ends_at'      => ['nullable', 'date', 'after_or_equal:starts_at'],
                'is_active'    => ['boolean'],
            ]);

            $campaign = LoyaltyCampaign::create([
                ...$validated,
                'created_by' => $request->user()?->id,
            ]);

            return (new JsonResource($campaign))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_LOYALTY_CAMPAIGN');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_LOYALTY_CAMPAIGN');
        }
    }

    /**
     * Show a single loyalty campaign.
     *
     * @param  LoyaltyCampaign  $loyaltyCampaign
     * @return JsonResource|JsonResponse
     */
    public function show(LoyaltyCampaign $loyaltyCampaign): JsonResource|JsonResponse
    {
        try {
            return new JsonResource($loyaltyCampaign);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_LOYALTY_CAMPAIGN');
        }
    }

    /**
     * Update an existing loyalty campaign.
     *
     * @param  Request          $request
     * @param  LoyaltyCampaign  $loyaltyCampaign
     * @return JsonResource|JsonResponse
     */
    public function update(Request $request, LoyaltyCampaign $loyaltyCampaign): JsonResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'         => ['sometimes', 'string', 'max:200'],
                'type'         => ['sometimes', 'string', 'max:50'],
                'multiplier'   => ['sometimes', 'nullable', 'numeric', 'min:1'],
                'bonus_points' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'conditions'   => ['sometimes', 'nullable', 'array'],
                'starts_at'    => ['sometimes', 'nullable', 'date'],
                'ends_at'      => ['sometimes', 'nullable', 'date'],
                'is_active'    => ['sometimes', 'boolean'],
            ]);

            $loyaltyCampaign->update($validated);
            return new JsonResource($loyaltyCampaign->refresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_LOYALTY_CAMPAIGN');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_LOYALTY_CAMPAIGN');
        }
    }

    /**
     * Delete a loyalty campaign.
     *
     * @param  LoyaltyCampaign  $loyaltyCampaign
     * @return JsonResponse
     */
    public function destroy(LoyaltyCampaign $loyaltyCampaign): JsonResponse
    {
        try {
            $loyaltyCampaign->delete();
            return response()->json(['success' => true], 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_LOYALTY_CAMPAIGN');
        }
    }
}
