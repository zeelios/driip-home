<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use App\Domain\Order\Models\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line item within a courier COD remittance batch.
 *
 * Links a remittance batch to a specific shipment and order, recording
 * the collected COD amount, the courier's shipping fee, any other fees,
 * and the resulting net amount remitted to Driip.
 *
 * @property string                $id
 * @property string                $remittance_id
 * @property string                $shipment_id
 * @property string                $order_id
 * @property int                   $cod_amount
 * @property int                   $shipping_fee
 * @property int                   $other_fees
 * @property int                   $net_amount
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class CourierCODRemittanceItem extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'courier_cod_remittance_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'remittance_id',
        'shipment_id',
        'order_id',
        'cod_amount',
        'shipping_fee',
        'other_fees',
        'net_amount',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'cod_amount'   => 'integer',
        'shipping_fee' => 'integer',
        'other_fees'   => 'integer',
        'net_amount'   => 'integer',
    ];

    /**
     * Get the remittance batch this item belongs to.
     *
     * @return BelongsTo<CourierCODRemittance, CourierCODRemittanceItem>
     */
    public function remittance(): BelongsTo
    {
        return $this->belongsTo(CourierCODRemittance::class, 'remittance_id');
    }

    /**
     * Get the shipment associated with this remittance line.
     *
     * @return BelongsTo<Shipment, CourierCODRemittanceItem>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Get the order associated with this remittance line.
     *
     * @return BelongsTo<Order, CourierCODRemittanceItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
