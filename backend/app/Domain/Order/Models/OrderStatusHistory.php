<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderStatusHistory model recording each status transition for an order.
 *
 * Provides an immutable audit trail of every state change, who made it,
 * and whether the entry should be surfaced to the customer.
 * This model intentionally has no updated_at timestamp.
 *
 * @property string      $id
 * @property string      $order_id
 * @property string|null $from_status
 * @property string      $to_status
 * @property string|null $note
 * @property bool        $is_customer_visible
 * @property string|null $created_by
 * @property \Carbon\Carbon|null $created_at
 */
class OrderStatusHistory extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_status_history';

    /**
     * Disable automatic timestamp management.
     *
     * Only created_at is stored; updated_at does not exist on this table.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var string|null The name of the created_at column. */
    const CREATED_AT = 'created_at';

    /** @var string|null The name of the updated_at column — not used. */
    const UPDATED_AT = null;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'note',
        'is_customer_visible',
        'created_by',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_customer_visible' => 'boolean',
        'created_at'          => 'datetime',
    ];

    /**
     * Get the order this history entry belongs to.
     *
     * @return BelongsTo<Order, OrderStatusHistory>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the staff member who performed this status change.
     *
     * Returns null if the change was made by the system or the user was deleted.
     *
     * @return BelongsTo<User, OrderStatusHistory>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
