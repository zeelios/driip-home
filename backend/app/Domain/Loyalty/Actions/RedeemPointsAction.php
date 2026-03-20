<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Action responsible for redeeming loyalty points from an account.
 *
 * Validates that the account has a sufficient balance before deducting the
 * requested points and creating an immutable debit transaction.
 */
class RedeemPointsAction
{
    /**
     * Deduct points from the specified loyalty account.
     *
     * Validates that points is positive and that the account balance is
     * sufficient before proceeding. All writes occur inside a transaction.
     *
     * @param  string      $loyaltyAccountId  UUID of the target loyalty account.
     * @param  int         $points            Number of points to redeem (must be > 0).
     * @param  string|null $orderId           Optional UUID of the order being discounted.
     * @param  string|null $description       Human-readable reason for the redemption.
     * @param  string|null $createdBy         UUID of the staff member who initiated this.
     * @return LoyaltyTransaction             The newly created debit transaction.
     *
     * @throws InvalidArgumentException If points <= 0 or balance is insufficient.
     */
    public function execute(
        string  $loyaltyAccountId,
        int     $points,
        ?string $orderId     = null,
        ?string $description = null,
        ?string $createdBy   = null,
    ): LoyaltyTransaction {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points to redeem must be greater than zero.');
        }

        return DB::transaction(function () use (
            $loyaltyAccountId, $points, $orderId, $description, $createdBy
        ): LoyaltyTransaction {
            /** @var LoyaltyAccount $account */
            $account = LoyaltyAccount::lockForUpdate()->findOrFail($loyaltyAccountId);

            if ($account->points_balance < $points) {
                throw new InvalidArgumentException(
                    "Insufficient points balance. Available: {$account->points_balance}, requested: {$points}."
                );
            }

            $account->decrement('points_balance', $points);
            $account->refresh();

            /** @var LoyaltyTransaction $transaction */
            $transaction = LoyaltyTransaction::create([
                'loyalty_account_id' => $account->id,
                'type'               => 'redeem',
                'points'             => -$points,
                'balance_after'      => $account->points_balance,
                'reference_type'     => $orderId !== null ? 'order' : null,
                'reference_id'       => $orderId,
                'description'        => $description,
                'created_by'         => $createdBy,
                'created_at'         => now(),
            ]);

            return $transaction;
        });
    }
}
