<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shipment model representing a parcel dispatched via a courier for an order.
 *
 * Tracks the full lifecycle of a shipment from draft creation through to
 * delivery or return, including COD amounts, fees, and courier-side
 * request/response payloads.
 *
 * @property string                $id
 * @property string                $order_id
 * @property string                $courier_code
 * @property string|null           $tracking_number
 * @property string|null           $internal_reference
 * @property string                $status
 * @property string|null           $label_url
 * @property int                   $cod_amount
 * @property bool                  $cod_collected
 * @property int|null              $shipping_fee_quoted
 * @property int|null              $shipping_fee_actual
 * @property string|null           $weight_kg
 * @property \Carbon\Carbon|null   $estimated_delivery_at
 * @property \Carbon\Carbon|null   $delivered_at
 * @property int                   $failed_attempts
 * @property array<string,mixed>|null $courier_request
 * @property array<string,mixed>|null $courier_response
 * @property string                $created_by
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class Shipment extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'shipments';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'courier_code',
        'tracking_number',
        'internal_reference',
        'status',
        'label_url',
        'cod_amount',
        'cod_collected',
        'shipping_fee_quoted',
        'shipping_fee_actual',
        'weight_kg',
        'estimated_delivery_at',
        'delivered_at',
        'failed_attempts',
        'courier_request',
        'courier_response',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'cod_amount'            => 'integer',
        'cod_collected'         => 'boolean',
        'shipping_fee_quoted'   => 'integer',
        'shipping_fee_actual'   => 'integer',
        'failed_attempts'       => 'integer',
        'estimated_delivery_at' => 'date',
        'delivered_at'          => 'datetime',
        'courier_request'       => 'array',
        'courier_response'      => 'array',
    ];

    /**
     * Get the order this shipment belongs to.
     *
     * @return BelongsTo<Order, Shipment>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the staff member who created this shipment.
     *
     * @return BelongsTo<User, Shipment>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all tracking events recorded for this shipment.
     *
     * @return HasMany<ShipmentTrackingEvent>
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class, 'shipment_id')
            ->orderByDesc('occurred_at');
    }

    /**
     * Determine whether this shipment has been successfully delivered.
     *
     * @return bool
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
