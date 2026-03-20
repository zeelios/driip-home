<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyCampaign;

/**
 * Creates a new loyalty campaign.
 *
 * Accepts a validated data array and an optional creator UUID, then
 * persists the campaign record.
 */
class CreateCampaignAction
{
    /**
     * Execute the campaign creation.
     *
     * @param  array<string,mixed>  $data       Validated campaign attributes.
     * @param  string|null          $createdBy  UUID of the authenticated staff member.
     * @return LoyaltyCampaign  The newly created campaign.
     */
    public function execute(array $data, ?string $createdBy = null): LoyaltyCampaign
    {
        return LoyaltyCampaign::create([
            ...$data,
            'created_by' => $createdBy,
        ]);
    }
}
