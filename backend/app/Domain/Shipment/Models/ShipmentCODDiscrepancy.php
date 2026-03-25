<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COD discrepancy detected between courier claim and internal records.
 *
 * Tracks when a courier claims a shipment is delivered/COD collected
 * but Driip has not received the remittance.
 *
 * @property string                $id
 * @property string                $shipment_id
 * @property string                $order_id
 * @property string                $courier_code
 * @property string                $tracking_number
 * @property int                   $cod_amount
 * @property string                $discrepancy_type
 * @property string                $status
 * @property string                $description
 * @property string|null           $courier_claim
 * @property string|null           $internal_record
 * @property string|null           $resolution_notes
 * @property string|null           $resolved_by
 * @property \Carbon\Carbon         $detected_at
 * @property \Carbon\Carbon|null   $resolved_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class ShipmentCODDiscrepancy extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'shipment_cod_discrepancies';

    protected $fillable = [
        'shipment_id',
        'order_id',
        'courier_code',
        'tracking_number',
        'cod_amount',
        'discrepancy_type',
        'status',
        'description',
        'courier_claim',
        'internal_record',
        'resolution_notes',
        'resolved_by',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'cod_amount' => 'integer',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function markResolved(string $notes, ?string $userId = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }

    public function markInvestigating(string $notes): void
    {
        $this->update([
            'status' => 'investigating',
            'resolution_notes' => $notes,
        ]);
    }

    public function markDismissed(string $reason, ?string $userId = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolution_notes' => $reason,
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }
}
