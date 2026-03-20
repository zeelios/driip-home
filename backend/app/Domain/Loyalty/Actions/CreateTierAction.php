<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyTier;
use Illuminate\Support\Str;

/**
 * Creates a new loyalty tier definition.
 *
 * Accepts a validated data array, auto-generates a slug from the name if
 * one is not provided, and persists the tier.
 */
class CreateTierAction
{
    /**
     * Execute the tier creation.
     *
     * @param  array<string,mixed>  $data  Validated tier attributes.
     * @return LoyaltyTier  The newly created tier.
     */
    public function execute(array $data): LoyaltyTier
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return LoyaltyTier::create($data);
    }
}
