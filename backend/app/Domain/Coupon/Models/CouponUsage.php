<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Models;

use App\Domain\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CouponUsage model recording each time a coupon was redeemed against an order.
 *
 * This table is append-only; records are created when ApplyCouponAction is
 * invoked and are never modified afterwards. No soft deletes — historical
 * redemption data must be preserved.
 *
 * @property string               $id
 * @property string               $coupon_id
 * @property string|null          $customer_id
 * @property string               $order_id
 * @property int                  $discount_amount
 * @property \Carbon\Carbon       $used_at
 */
class CouponUsage extends Model
{
    use HasUuids;

    /**
     * Disable automatic timestamp management.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var string The table associated with this model. */
    protected $table = 'coupon_usages';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'coupon_id',
        'customer_id',
        'order_id',
        'discount_amount',
        'used_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'discount_amount' => 'integer',
        'used_at'         => 'datetime',
    ];

    /**
     * Get the coupon that was redeemed in this usage record.
     *
     * @return BelongsTo<Coupon, CouponUsage>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * Get the customer who redeemed the coupon, if known.
     *
     * @return BelongsTo<Customer, CouponUsage>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
