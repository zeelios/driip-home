<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Customer\Models\Customer;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order model representing a customer purchase in the Driip platform.
 *
 * Tracks the full lifecycle of an order from placement through delivery,
 * including payment, shipping address, pricing, VAT, and fulfilment data.
 * Guest orders are supported when no registered customer is linked.
 *
 * @property string                    $id
 * @property string                    $order_number
 * @property string|null               $customer_id
 * @property string|null               $guest_name
 * @property string|null               $guest_email
 * @property string|null               $guest_phone
 * @property string                    $status
 * @property string                    $payment_status
 * @property string|null               $payment_method
 * @property string|null               $payment_reference
 * @property \Carbon\Carbon|null       $paid_at
 * @property int                       $subtotal
 * @property string|null               $coupon_id
 * @property string|null               $coupon_code
 * @property int                       $coupon_discount
 * @property int                       $loyalty_points_used
 * @property int                       $loyalty_discount
 * @property int                       $shipping_fee
 * @property float                     $vat_rate
 * @property int                       $vat_amount
 * @property int                       $total_before_tax
 * @property int                       $total_after_tax
 * @property string|null               $tax_code
 * @property int                       $cost_total
 * @property string                    $shipping_name
 * @property string                    $shipping_phone
 * @property string                    $shipping_province
 * @property string|null               $shipping_district
 * @property string|null               $shipping_ward
 * @property string                    $shipping_address
 * @property string|null               $shipping_zip
 * @property string|null               $notes
 * @property string|null               $internal_notes
 * @property array<int,string>         $tags
 * @property string|null               $source
 * @property string|null               $utm_source
 * @property string|null               $utm_medium
 * @property string|null               $utm_campaign
 * @property string|null               $warehouse_id
 * @property string|null               $assigned_to
 * @property string|null               $packed_by
 * @property \Carbon\Carbon|null       $packed_at
 * @property \Carbon\Carbon|null       $confirmed_at
 * @property \Carbon\Carbon|null       $delivered_at
 * @property \Carbon\Carbon|null       $cancelled_at
 * @property string|null               $cancellation_reason
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 * @property \Carbon\Carbon|null       $deleted_at
 */
class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'orders';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_number',
        'customer_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'subtotal',
        'coupon_id',
        'coupon_code',
        'coupon_discount',
        'loyalty_points_used',
        'loyalty_discount',
        'shipping_fee',
        'vat_rate',
        'vat_amount',
        'total_before_tax',
        'total_after_tax',
        'tax_code',
        'cost_total',
        'shipping_name',
        'shipping_phone',
        'shipping_province',
        'shipping_district',
        'shipping_ward',
        'shipping_address',
        'shipping_zip',
        'notes',
        'internal_notes',
        'tags',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'warehouse_id',
        'assigned_to',
        'packed_by',
        'packed_at',
        'confirmed_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'tags'          => 'array',
        'paid_at'       => 'datetime',
        'packed_at'     => 'datetime',
        'confirmed_at'  => 'datetime',
        'delivered_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
        'subtotal'      => 'integer',
        'coupon_discount'   => 'integer',
        'loyalty_points_used' => 'integer',
        'loyalty_discount'  => 'integer',
        'shipping_fee'  => 'integer',
        'vat_amount'    => 'integer',
        'total_before_tax'  => 'integer',
        'total_after_tax'   => 'integer',
        'cost_total'    => 'integer',
    ];

    /**
     * Get the customer who placed this order.
     *
     * @return BelongsTo<Customer, Order>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get all line items belonging to this order.
     *
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the full status transition history for this order.
     *
     * @return HasMany<OrderStatusHistory>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    /**
     * Get all claims raised against this order.
     *
     * @return HasMany<OrderClaim>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(OrderClaim::class, 'order_id');
    }

    /**
     * Get all returns associated with this order.
     *
     * @return HasMany<OrderReturn>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'order_id');
    }

    /**
     * Get all shipments for this order.
     *
     * Uses a string class name to avoid a circular dependency with the Shipment domain.
     *
     * @return HasMany<\App\Domain\Shipment\Models\Shipment>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany('App\Domain\Shipment\Models\Shipment', 'order_id');
    }

    /**
     * Get the warehouse fulfilling this order.
     *
     * @return BelongsTo<\App\Domain\Warehouse\Models\Warehouse, Order>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Warehouse\Models\Warehouse', 'warehouse_id');
    }

    /**
     * Get the staff member assigned to this order as a sales representative.
     *
     * @return BelongsTo<User, Order>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the coupon applied to this order.
     *
     * @return BelongsTo<\App\Domain\Coupon\Models\Coupon, Order>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Coupon\Models\Coupon', 'coupon_id');
    }

    /**
     * Get the tax invoice issued for this order.
     *
     * @return HasOne<\App\Domain\Tax\Models\TaxInvoice>
     */
    public function taxInvoice(): HasOne
    {
        return $this->hasOne('App\Domain\Tax\Models\TaxInvoice', 'order_id');
    }

    /**
     * Determine whether the order can still be edited.
     *
     * Only pending and confirmed orders may be modified.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * Determine whether the order can be cancelled.
     *
     * Orders that have already been packed or further along cannot be cancelled.
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing'], true);
    }

    /**
     * Scope: filter orders in the pending status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: filter orders in the delivered status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope: filter orders in the cancelled status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }
}
