<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An individual courier tracking event attached to a shipment.
 *
 * Records the courier's status, a human-readable message, an optional
 * location string, and the raw courier webhook payload for auditing.
 * This table has no standard created_at/updated_at columns — timestamps
 * are captured via occurred_at (event time) and synced_at (ingestion time).
 *
 * @property string                $id
 * @property string                $shipment_id
 * @property string                $status
 * @property string|null           $courier_status_code
 * @property string                $message
 * @property string|null           $location
 * @property \Carbon\Carbon        $occurred_at
 * @property \Carbon\Carbon        $synced_at
 * @property array<string,mixed>|null $raw_data
 */
class ShipmentTrackingEvent extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'shipment_tracking_events';

    /**
     * Disable Eloquent's automatic created_at / updated_at management.
     *
     * This table uses occurred_at and synced_at instead.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'shipment_id',
        'status',
        'courier_status_code',
        'message',
        'location',
        'occurred_at',
        'synced_at',
        'raw_data',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'occurred_at' => 'datetime',
        'synced_at'   => 'datetime',
        'raw_data'    => 'array',
    ];

    /**
     * Get the shipment this tracking event belongs to.
     *
     * @return BelongsTo<Shipment, ShipmentTrackingEvent>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
