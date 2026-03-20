<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Coupon model representing a discount code that customers can apply at checkout.
 *
 * Supports three discount types: percentage, fixed VND amount, or free shipping.
 * Coupons can be restricted by order amount, item count, usage limits, and date range.
 * The isValid() helper encapsulates all eligibility checks except customer-level ones.
 *
 * @property string               $id
 * @property string               $code
 * @property string               $name
 * @property string|null          $description
 * @property string               $type
 * @property float                $value
 * @property int|null             $min_order_amount
 * @property int|null             $min_items
 * @property int|null             $max_discount_amount
 * @property string               $applies_to
 * @property array<int,string>    $applies_to_ids
 * @property int|null             $max_uses
 * @property int                  $max_uses_per_customer
 * @property int                  $used_count
 * @property bool                 $is_public
 * @property bool                 $is_active
 * @property \Carbon\Carbon|null  $starts_at
 * @property \Carbon\Carbon|null  $expires_at
 * @property string|null          $created_by
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 */
class Coupon extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'coupons';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'min_items',
        'max_discount_amount',
        'applies_to',
        'applies_to_ids',
        'max_uses',
        'max_uses_per_customer',
        'used_count',
        'is_public',
        'is_active',
        'starts_at',
        'expires_at',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active'            => 'boolean',
        'is_public'            => 'boolean',
        'applies_to_ids'       => 'array',
        'value'                => 'decimal:2',
        'starts_at'            => 'datetime',
        'expires_at'           => 'datetime',
        'min_order_amount'     => 'integer',
        'min_items'            => 'integer',
        'max_discount_amount'  => 'integer',
        'max_uses'             => 'integer',
        'max_uses_per_customer' => 'integer',
        'used_count'           => 'integer',
    ];

    /**
     * Get all usage records for this coupon.
     *
     * @return HasMany<CouponUsage>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    /**
     * Determine whether this coupon is currently valid for redemption.
     *
     * Checks: active flag, max global usage limit, and active date window.
     * Does NOT check customer-specific usage limits — that is the responsibility
     * of ValidateCouponAction.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether this coupon's validity window has elapsed.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }
}
