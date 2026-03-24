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
        'deposit_amount',
        'deposit_paid_at',
        'deposit_proof_urls',
        'payment_notes',
        'public_token',
        'token_expires_at',
        'referral_code',
        'sales_rep_id',
        'commission_amount',
        'commission_rate',
        'commission_status',
        'commission_paid_reference',
        'commission_paid_at',
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
        'tags' => 'array',
        'paid_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'deposit_proof_urls' => 'array',
        'packed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'commission_paid_at' => 'datetime',
        'subtotal' => 'integer',
        'coupon_discount' => 'integer',
        'loyalty_points_used' => 'integer',
        'loyalty_discount' => 'integer',
        'shipping_fee' => 'integer',
        'vat_amount' => 'integer',
        'total_before_tax' => 'integer',
        'total_after_tax' => 'integer',
        'cost_total' => 'integer',
    ];

    /** @var array<string> Eager load relationships. */
    protected $with = [

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
     * Get all activity log entries for this order.
     *
     * @return HasMany<OrderActivity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class, 'order_id');
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
     * Get the remaining balance due on this order.
     *
     * @return int
     */
    public function balanceDue(): int
    {
        return max(0, $this->total_after_tax - $this->deposit_amount);
    }

    /**
     * Determine if the order has a deposit recorded.
     *
     * @return bool
     */
    public function hasDeposit(): bool
    {
        return $this->deposit_amount > 0;
    }

    /**
     * Determine if the order is fully paid.
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this->deposit_amount >= $this->total_after_tax;
    }

    /**
     * Record a deposit payment.
     *
     * @param  int       $amount
     * @param  string[]  $proofUrls
     * @param  string|null $notes
     * @return void
     */
    public function recordDeposit(int $amount, array $proofUrls = [], ?string $notes = null): void
    {
        $newDepositAmount = $this->deposit_amount + $amount;

        $this->update([
            'deposit_amount' => $newDepositAmount,
            'deposit_paid_at' => now(),
            'deposit_proof_urls' => array_merge($this->deposit_proof_urls ?? [], $proofUrls),
            'payment_notes' => $notes ? ($this->payment_notes ? $this->payment_notes . "\n" . $notes : $notes) : $this->payment_notes,
            'payment_status' => $newDepositAmount >= $this->total_after_tax ? 'paid' : 'partial',
        ]);
    }

    /**
     * Mark order as fully paid.
     *
     * @param  string      $method
     * @param  string|null $reference
     * @return void
     */
    public function markFullyPaid(string $method, ?string $reference = null): void
    {
        $this->update([
            'deposit_amount' => $this->total_after_tax,
            'payment_status' => 'paid',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ]);
    }

    /**
     * Scope: filter orders by payment method.
     *
     * @param  Builder<Order>  $query
     * @param  string          $method
     * @return Builder<Order>
     */
    public function scopeWherePaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: filter prepaid orders (non-COD).
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWherePrepaid(Builder $query): Builder
    {
        return $query->whereIn('payment_method', ['bank_transfer', 'momo', 'zalopay', 'vnpay', 'credit_card', 'cash']);
    }

    /**
     * Scope: filter COD orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereCOD(Builder $query): Builder
    {
        return $query->where('payment_method', 'cod');
    }

    /**
     * Scope: filter by payment status.
     *
     * @param  Builder<Order>  $query
     * @param  string          $status
     * @return Builder<Order>
     */
    public function scopeWherePaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope: filter unpaid orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'unpaid');
    }

    /**
     * Scope: filter partially paid orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWherePartiallyPaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'partial');
    }

    /**
     * Scope: filter fully paid orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereFullyPaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope: filter COD orders pending collection.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereCODPendingCollection(Builder $query): Builder
    {
        return $query->where('payment_method', 'cod')
            ->where('status', 'delivered')
            ->whereNull('cod_collected_at');
    }

    /**
     * Scope: filter COD orders with collected payment.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereCODCollected(Builder $query): Builder
    {
        return $query->where('payment_method', 'cod')
            ->whereNotNull('cod_collected_at');
    }

    /**
     * Scope: filter COD orders with discrepancies.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereCODDiscrepancy(Builder $query): Builder
    {
        return $query->where('payment_method', 'cod')
            ->where('cod_reconciliation_status', 'disputed')
            ->whereNotNull('cod_discrepancy_amount');
    }

    /**
     * Scope: filter orders with balance due.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereHasBalanceDue(Builder $query): Builder
    {
        return $query->whereRaw('(total_after_tax - COALESCE(deposit_amount, 0)) > 0');
    }

    /**
     * Scope: filter orders with overdue balance.
     *
     * @param  Builder<Order>  $query
     * @param  int             $days
     * @return Builder<Order>
     */
    public function scopeWhereBalanceOverdue(Builder $query, int $days): Builder
    {
        return $query->whereHasBalanceDue()
            ->where('created_at', '<=', now()->subDays($days));
    }

    /**
     * Get all payments for this order.
     *
     * @return HasMany<OrderPayment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id')->orderBy('created_at', 'desc');
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
