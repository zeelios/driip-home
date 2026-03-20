<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyTier;

/**
 * Updates an existing loyalty tier definition.
 *
 * Accepts a validated partial-update data array and applies the changes
 * to the given tier model.
 */
class UpdateTierAction
{
    /**
     * Execute the tier update.
     *
     * @param  LoyaltyTier          $tier  The tier model to update.
     * @param  array<string,mixed>  $data  Validated attributes to apply.
     * @return LoyaltyTier  The updated tier (refreshed from the database).
     */
    public function execute(LoyaltyTier $tier, array $data): LoyaltyTier
    {
        $tier->update($data);

        return $tier->refresh();
    }
}
