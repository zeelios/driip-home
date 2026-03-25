<?php

declare(strict_types=1);

namespace App\Domain\Payment\Models;

use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending deposit tracking for bank transfer matching.
 *
 * Represents an expected incoming bank transfer that needs
 * to be matched against actual bank transactions.
 *
 * @property string                $id
 * @property string                $order_id
 * @property int                   $expected_amount
 * @property int                   $amount_tolerance
 * @property string                $transfer_content_pattern
 * @property string|null           $bank_config_id
 * @property string                $status
 * @property \Carbon\Carbon         $expires_at
 * @property string|null           $matched_transaction_id
 * @property \Carbon\Carbon|null   $matched_at
 * @property string|null           $matched_by
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class PendingDeposit extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'pending_deposits';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'expected_amount',
        'amount_tolerance',
        'transfer_content_pattern',
        'bank_config_id',
        'status',
        'expires_at',
        'matched_transaction_id',
        'matched_at',
        'matched_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'expected_amount' => 'integer',
        'amount_tolerance' => 'integer',
        'expires_at' => 'datetime',
        'matched_at' => 'datetime',
    ];

    /**
     * Get the order this pending deposit belongs to.
     *
     * @return BelongsTo<Order, PendingDeposit>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the bank config for this pending deposit.
     *
     * @return BelongsTo<BankConfig, PendingDeposit>
     */
    public function bankConfig(): BelongsTo
    {
        return $this->belongsTo(BankConfig::class, 'bank_config_id');
    }

    /**
     * Get the user who matched this deposit.
     *
     * @return BelongsTo<User, PendingDeposit>
     */
    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    /**
     * Check if this pending deposit is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this pending deposit can be matched.
     *
     * @return bool
     */
    public function canBeMatched(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark this deposit as matched.
     *
     * @param  string      $transactionId
     * @param  string|null $matchedBy
     * @return void
     */
    public function markMatched(string $transactionId, ?string $matchedBy = null): void
    {
        $this->update([
            'status' => 'matched',
            'matched_transaction_id' => $transactionId,
            'matched_at' => now(),
            'matched_by' => $matchedBy,
        ]);
    }

    /**
     * Mark this deposit as expired.
     *
     * @return void
     */
    public function markExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Mark this deposit as cancelled.
     *
     * @return void
     */
    public function markCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the acceptable amount range for matching.
     *
     * @return array{min: int, max: int}
     */
    public function getAcceptableAmountRange(): array
    {
        return [
            'min' => $this->expected_amount - $this->amount_tolerance,
            'max' => $this->expected_amount + $this->amount_tolerance,
        ];
    }

    /**
     * Check if an amount is within the acceptable range.
     *
     * @param  int  $amount
     * @return bool
     */
    public function isAmountAcceptable(int $amount): bool
    {
        $range = $this->getAcceptableAmountRange();

        return $amount >= $range['min'] && $amount <= $range['max'];
    }
}
