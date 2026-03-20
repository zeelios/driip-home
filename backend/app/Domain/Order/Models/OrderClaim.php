<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderClaim model representing a dispute or complaint raised against an order.
 *
 * Claims may be filed by customers or internal staff and can reference a
 * specific order item. Each claim progresses through a defined status
 * workflow from open through to resolution or rejection.
 *
 * @property string                    $id
 * @property string                    $claim_number
 * @property string                    $order_id
 * @property string|null               $order_item_id
 * @property string                    $type
 * @property string                    $status
 * @property string                    $description
 * @property array<int,string>         $evidence_urls
 * @property string|null               $resolution
 * @property string|null               $resolution_notes
 * @property int|null                  $refund_amount
 * @property string|null               $assigned_to
 * @property bool                      $created_by_customer
 * @property string|null               $created_by
 * @property \Carbon\Carbon|null       $resolved_at
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 */
class OrderClaim extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_claims';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'claim_number',
        'order_id',
        'order_item_id',
        'type',
        'status',
        'description',
        'evidence_urls',
        'resolution',
        'resolution_notes',
        'refund_amount',
        'assigned_to',
        'created_by_customer',
        'created_by',
        'resolved_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'evidence_urls'      => 'array',
        'created_by_customer' => 'boolean',
        'resolved_at'        => 'datetime',
    ];

    /**
     * Get the order this claim is raised against.
     *
     * @return BelongsTo<Order, OrderClaim>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the specific order item referenced by this claim.
     *
     * Returns null when the claim covers the entire order.
     *
     * @return BelongsTo<OrderItem, OrderClaim>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * Get the staff member assigned to handle this claim.
     *
     * @return BelongsTo<User, OrderClaim>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the staff member who created this claim.
     *
     * Returns null if the claim was created by a customer or the user was deleted.
     *
     * @return BelongsTo<User, OrderClaim>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
