<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual payment record for an order.
 *
 * Tracks each payment event (deposits, final payments, COD collections,
 * refunds, adjustments) with full audit trail including who recorded it
 * and any proof documentation.
 *
 * @property string                    $id
 * @property string                    $order_id
 * @property int                       $amount
 * @property string                    $payment_method
 * @property string                    $payment_type
 * @property string|null               $reference
 * @property list<string>              $proof_urls
 * @property string|null               $notes
 * @property string|null               $recorded_by
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 */
class OrderPayment extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_payments';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'payment_type',
        'reference',
        'proof_urls',
        'notes',
        'recorded_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'proof_urls' => 'array',
        'amount' => 'integer',
    ];

    /**
     * Get the order this payment belongs to.
     *
     * @return BelongsTo<Order, OrderPayment>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the staff member who recorded this payment.
     *
     * @return BelongsTo<User, OrderPayment>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Determine if this is a deposit payment.
     *
     * @return bool
     */
    public function isDeposit(): bool
    {
        return $this->payment_type === 'deposit';
    }

    /**
     * Determine if this is a COD collection.
     *
     * @return bool
     */
    public function isCodCollection(): bool
    {
        return $this->payment_type === 'cod_collection';
    }

    /**
     * Determine if this is a refund.
     *
     * @return bool
     */
    public function isRefund(): bool
    {
        return $this->payment_type === 'refund';
    }
}
