<?php

declare(strict_types=1);

namespace App\Domain\Customer\Models;

use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Order\Models\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer model representing a shopper registered in the Driip platform.
 *
 * Tracks personal information, contact details, referral relationships,
 * loyalty account linkage, and aggregate order statistics.
 *
 * @property string                    $id
 * @property string                    $customer_code
 * @property string                    $first_name
 * @property string                    $last_name
 * @property string|null               $email
 * @property string|null               $phone
 * @property \Carbon\Carbon|null       $phone_verified_at
 * @property string|null               $gender
 * @property \Carbon\Carbon|null       $date_of_birth
 * @property string|null               $avatar
 * @property string|null               $source
 * @property string|null               $referrer_id
 * @property string|null               $referral_code
 * @property array<int,string>         $tags
 * @property bool                      $is_blocked
 * @property string|null               $blocked_reason
 * @property int                       $total_orders
 * @property int                       $total_spent
 * @property \Carbon\Carbon|null       $last_ordered_at
 * @property string|null               $notes
 * @property string|null               $zalo_id
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 * @property \Carbon\Carbon|null       $deleted_at
 */
class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'customers';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'customer_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_verified_at',
        'gender',
        'date_of_birth',
        'avatar',
        'source',
        'referrer_id',
        'referral_code',
        'tags',
        'is_blocked',
        'blocked_reason',
        'total_orders',
        'total_spent',
        'last_ordered_at',
        'notes',
        'zalo_id',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'tags' => 'array',
        'is_blocked' => 'boolean',
        'date_of_birth' => 'date',
        'last_ordered_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'total_orders' => 'integer',
        'total_spent' => 'integer',
    ];

    /**
     * Get all addresses belonging to this customer.
     *
     * @return HasMany<CustomerAddress>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    /**
     * Get all interaction records for this customer.
     *
     * @return HasMany<CustomerInteraction>
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'customer_id');
    }

    /**
     * Get the customer who referred this customer.
     *
     * @return BelongsTo<Customer, Customer>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_id');
    }

    /**
     * Get all customers this customer has referred.
     *
     * @return HasMany<Customer>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Customer::class, 'referrer_id');
    }

    /**
     * Get the loyalty account associated with this customer.
     *
     * @return HasOne<LoyaltyAccount>
     */
    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(LoyaltyAccount::class, 'customer_id');
    }

    /**
     * Get all orders for this customer.
     *
     * @return HasMany<Order>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the customer's full name.
     *
     * @return string
     */
    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
