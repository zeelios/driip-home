<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Domain\Loyalty\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for crediting loyalty points to an account.
 *
 * Adds the given points to the account balance and lifetime totals,
 * records an immutable transaction entry, and automatically upgrades
 * the customer's tier if the new lifetime total crosses a threshold.
 */
class EarnPointsAction
{
    /**
     * Credit points to the specified loyalty account.
     *
     * Steps performed inside a single database transaction:
     *  1. Lock the loyalty account row for update.
     *  2. Increment points_balance and lifetime_points.
     *  3. Create an immutable LoyaltyTransaction ledger entry.
     *  4. Check all tiers and upgrade if a higher threshold is now met.
     *
     * @param  string      $loyaltyAccountId  UUID of the target loyalty account.
     * @param  int         $points            Number of points to credit (must be > 0).
     * @param  string|null $referenceType     Optional polymorphic type (e.g. "order").
     * @param  string|null $referenceId       Optional polymorphic ID (e.g. order UUID).
     * @param  string|null $description       Human-readable reason for the transaction.
     * @param  string|null $createdBy         UUID of the staff member who initiated this.
     * @return LoyaltyTransaction             The newly created transaction record.
     */
    public function execute(
        string  $loyaltyAccountId,
        int     $points,
        ?string $referenceType = null,
        ?string $referenceId   = null,
        ?string $description   = null,
        ?string $createdBy     = null,
    ): LoyaltyTransaction {
        return DB::transaction(function () use (
            $loyaltyAccountId, $points, $referenceType, $referenceId, $description, $createdBy
        ): LoyaltyTransaction {
            /** @var LoyaltyAccount $account */
            $account = LoyaltyAccount::lockForUpdate()->findOrFail($loyaltyAccountId);

            $account->increment('points_balance', $points);
            $account->increment('lifetime_points', $points);
            $account->refresh();

            /** @var LoyaltyTransaction $transaction */
            $transaction = LoyaltyTransaction::create([
                'loyalty_account_id' => $account->id,
                'type'               => 'earn',
                'points'             => $points,
                'balance_after'      => $account->points_balance,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'description'        => $description,
                'created_by'         => $createdBy,
                'created_at'         => now(),
            ]);

            $this->checkAndUpgradeTier($account);

            return $transaction;
        });
    }

    /**
     * Evaluate all tiers and upgrade the account if lifetime_points qualify.
     *
     * Selects the highest tier whose min_lifetime_points threshold is met
     * and updates the account only if the tier has changed.
     *
     * @param  LoyaltyAccount  $account  The account to evaluate (already refreshed).
     * @return void
     */
    private function checkAndUpgradeTier(LoyaltyAccount $account): void
    {
        $bestTier = LoyaltyTier::where('min_lifetime_points', '<=', $account->lifetime_points)
            ->orderByDesc('min_lifetime_points')
            ->first();

        if ($bestTier !== null && $bestTier->id !== $account->tier_id) {
            $account->update([
                'tier_id'          => $bestTier->id,
                'tier_achieved_at' => now(),
            ]);
        }
    }
}
