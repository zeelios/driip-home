<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyCampaign;

/**
 * Updates an existing loyalty campaign.
 *
 * Accepts a validated partial-update data array and applies the changes
 * to the given campaign model.
 */
class UpdateCampaignAction
{
    /**
     * Execute the campaign update.
     *
     * @param  LoyaltyCampaign      $campaign  The campaign model to update.
     * @param  array<string,mixed>  $data      Validated attributes to apply.
     * @return LoyaltyCampaign  The updated campaign (refreshed from the database).
     */
    public function execute(LoyaltyCampaign $campaign, array $data): LoyaltyCampaign
    {
        $campaign->update($data);

        return $campaign->refresh();
    }
}
