<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Models;

use App\Domain\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Loyalty account linked to a single customer.
 *
 * Tracks current redeemable points balance, cumulative lifetime points earned,
 * cumulative lifetime spending (in VND), and the current tier assignment
 * with optional tier expiry.
 *
 * @property string                $id
 * @property string                $customer_id
 * @property string|null           $tier_id
 * @property int                   $points_balance
 * @property int                   $lifetime_points
 * @property int                   $lifetime_spending
 * @property \Carbon\Carbon|null   $tier_achieved_at
 * @property \Carbon\Carbon|null   $tier_expires_at
 */
class LoyaltyAccount extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'loyalty_accounts';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'customer_id',
        'tier_id',
        'points_balance',
        'lifetime_points',
        'lifetime_spending',
        'tier_achieved_at',
        'tier_expires_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'points_balance'   => 'integer',
        'lifetime_points'  => 'integer',
        'lifetime_spending' => 'integer',
        'tier_achieved_at' => 'datetime',
        'tier_expires_at'  => 'datetime',
    ];

    /**
     * Get the customer who owns this loyalty account.
     *
     * @return BelongsTo<Customer, LoyaltyAccount>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the tier currently assigned to this account.
     *
     * @return BelongsTo<LoyaltyTier, LoyaltyAccount>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    /**
     * Get all point transactions for this account.
     *
     * @return HasMany<LoyaltyTransaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'loyalty_account_id');
    }
}
