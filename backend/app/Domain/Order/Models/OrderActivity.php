<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderActivity model recording every significant event in an order's lifecycle.
 *
 * Provides an immutable audit trail for compliance, customer service,
 * and debugging. Captures who did what, when, from where, and any
 * relevant metadata for context.
 *
 * @property string                    $id
 * @property string                    $order_id
 * @property string                    $actor_type
 * @property string|null               $actor_id
 * @property string                    $activity_type
 * @property string                    $description
 * @property array<string,mixed>       $metadata
 * @property string|null               $ip_address
 * @property string|null               $user_agent
 * @property \Carbon\Carbon|null       $created_at
 */
class OrderActivity extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_activities';

    /** @var bool Disable automatic timestamp management. */
    public $timestamps = false;

    /** @var string|null The name of the created_at column. */
    const CREATED_AT = 'created_at';

    /** @var string|null The name of the updated_at column — not used. */
    const UPDATED_AT = null;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'actor_type',
        'actor_id',
        'activity_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'metadata'    => 'array',
        'created_at'  => 'datetime',
    ];

    /**
     * Get the order this activity belongs to.
     *
     * @return BelongsTo<Order, OrderActivity>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
