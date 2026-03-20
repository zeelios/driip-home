<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Loyalty\Actions\CreateCampaignAction;
use App\Domain\Loyalty\Actions\UpdateCampaignAction;
use App\Domain\Loyalty\Models\LoyaltyCampaign;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Loyalty\CreateCampaignRequest;
use App\Http\Requests\Loyalty\UpdateCampaignRequest;
use App\Http\Resources\Loyalty\LoyaltyCampaignResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages loyalty campaign definitions — create, read, update, and delete.
 *
 * All write operations delegate to dedicated Action classes.
 */
class LoyaltyCampaignController extends BaseApiController
{
    /**
     * @param CreateCampaignAction $createCampaign Action responsible for creating a loyalty campaign.
     * @param UpdateCampaignAction $updateCampaign Action responsible for updating a loyalty campaign.
     */
    public function __construct(
        private readonly CreateCampaignAction $createCampaign,
        private readonly UpdateCampaignAction $updateCampaign,
    ) {}

    /**
     * List all loyalty campaigns, newest first.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $campaigns = LoyaltyCampaign::latest()->paginate(20);

        return LoyaltyCampaignResource::collection($campaigns);
    }

    /**
     * Create a new loyalty campaign.
     *
     * @param  CreateCampaignRequest  $request
     * @return LoyaltyCampaignResource|JsonResponse
     */
    public function store(CreateCampaignRequest $request): LoyaltyCampaignResource|JsonResponse
    {
        try {
            $campaign = $this->createCampaign->execute(
                $request->validated(),
                $request->user()?->id,
            );

            return (new LoyaltyCampaignResource($campaign))->response()->setStatusCode(201);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_LOYALTY_CAMPAIGN');
        }
    }

    /**
     * Show a single loyalty campaign.
     *
     * @param  LoyaltyCampaign  $loyaltyCampaign
     * @return LoyaltyCampaignResource
     */
    public function show(LoyaltyCampaign $loyaltyCampaign): LoyaltyCampaignResource
    {
        return new LoyaltyCampaignResource($loyaltyCampaign);
    }

    /**
     * Update an existing loyalty campaign.
     *
     * @param  UpdateCampaignRequest  $request
     * @param  LoyaltyCampaign        $loyaltyCampaign
     * @return LoyaltyCampaignResource|JsonResponse
     */
    public function update(UpdateCampaignRequest $request, LoyaltyCampaign $loyaltyCampaign): LoyaltyCampaignResource|JsonResponse
    {
        try {
            $campaign = $this->updateCampaign->execute($loyaltyCampaign, $request->validated());

            return new LoyaltyCampaignResource($campaign);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_LOYALTY_CAMPAIGN');
        }
    }

    /**
     * Delete a loyalty campaign (hard delete).
     *
     * @param  LoyaltyCampaign  $loyaltyCampaign
     * @return JsonResponse
     */
    public function destroy(LoyaltyCampaign $loyaltyCampaign): JsonResponse
    {
        try {
            $loyaltyCampaign->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_LOYALTY_CAMPAIGN');
        }
    }
}
