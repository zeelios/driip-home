<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A COD remittance batch sent by a courier partner.
 *
 * Represents a single remittance period from a courier covering all
 * collected cash-on-delivery amounts minus deducted fees. Reconciliation
 * matches each line item to an internal shipment.
 *
 * @property string                $id
 * @property string                $courier_code
 * @property string|null           $remittance_reference
 * @property \Carbon\Carbon        $period_from
 * @property \Carbon\Carbon        $period_to
 * @property int                   $total_cod_collected
 * @property int                   $total_fees_deducted
 * @property int                   $net_remittance
 * @property string                $status
 * @property \Carbon\Carbon|null   $received_at
 * @property string|null           $notes
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class CourierCODRemittance extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'courier_cod_remittances';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'courier_code',
        'remittance_reference',
        'period_from',
        'period_to',
        'total_cod_collected',
        'total_fees_deducted',
        'net_remittance',
        'status',
        'received_at',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'period_from'          => 'date',
        'period_to'            => 'date',
        'total_cod_collected'  => 'integer',
        'total_fees_deducted'  => 'integer',
        'net_remittance'       => 'integer',
        'received_at'          => 'datetime',
    ];

    /**
     * Get the individual line items that make up this remittance.
     *
     * @return HasMany<CourierCODRemittanceItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CourierCODRemittanceItem::class, 'remittance_id');
    }
}
