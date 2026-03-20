<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable ledger entry recording a points earn or redemption event.
 *
 * Each transaction captures the delta (points), the running balance after the
 * operation, an optional expiry date for earned points, and a polymorphic
 * reference to the originating entity (e.g. an order).
 *
 * @property string                $id
 * @property string                $loyalty_account_id
 * @property string                $type
 * @property int                   $points
 * @property int                   $balance_after
 * @property string|null           $reference_type
 * @property string|null           $reference_id
 * @property string|null           $description
 * @property \Carbon\Carbon|null   $expires_at
 * @property string|null           $created_by
 * @property \Carbon\Carbon        $created_at
 */
class LoyaltyTransaction extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'loyalty_transactions';

    /**
     * Disable automatic timestamp management; records are immutable append-only logs.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var string The name of the created-at column. */
    const CREATED_AT = 'created_at';

    /** @var null No updated_at column. */
    const UPDATED_AT = null;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'loyalty_account_id',
        'type',
        'points',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'expires_at',
        'created_by',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'expires_at'  => 'datetime',
        'created_at'  => 'datetime',
        'points'       => 'integer',
        'balance_after' => 'integer',
    ];

    /**
     * Get the loyalty account this transaction belongs to.
     *
     * @return BelongsTo<LoyaltyAccount, LoyaltyTransaction>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id');
    }

    /**
     * Get the staff member who initiated this transaction.
     *
     * @return BelongsTo<User, LoyaltyTransaction>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
