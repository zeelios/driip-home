<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderReturn model representing a physical return of goods from a customer.
 *
 * Returns may originate from a filed claim or be initiated independently.
 * Tracks the return shipment, refund disposition, and processing staff.
 *
 * @property string                    $id
 * @property string                    $return_number
 * @property string                    $order_id
 * @property string|null               $claim_id
 * @property string                    $status
 * @property array<int,mixed>          $return_items
 * @property string|null               $return_courier
 * @property string|null               $return_tracking
 * @property int|null                  $total_refund
 * @property string|null               $refund_method
 * @property string|null               $refund_reference
 * @property \Carbon\Carbon|null       $refunded_at
 * @property \Carbon\Carbon|null       $received_at
 * @property string|null               $processed_by
 * @property string|null               $notes
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 */
class OrderReturn extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_returns';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'return_number',
        'order_id',
        'claim_id',
        'status',
        'return_items',
        'return_courier',
        'return_tracking',
        'total_refund',
        'refund_method',
        'refund_reference',
        'refunded_at',
        'received_at',
        'processed_by',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'return_items' => 'array',
        'refunded_at'  => 'datetime',
        'received_at'  => 'datetime',
    ];

    /**
     * Get the order this return is associated with.
     *
     * @return BelongsTo<Order, OrderReturn>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the claim that triggered this return, if any.
     *
     * @return BelongsTo<OrderClaim, OrderReturn>
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(OrderClaim::class, 'claim_id');
    }

    /**
     * Get the staff member who processed this return.
     *
     * @return BelongsTo<User, OrderReturn>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
