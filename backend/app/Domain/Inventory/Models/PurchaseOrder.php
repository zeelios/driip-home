<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PurchaseOrder model representing a formal order placed with a supplier.
 *
 * Tracks the full lifecycle from draft through confirmation, partial receipt,
 * and full receipt. Contains cost totals and approval metadata.
 * Soft-deleted to preserve financial history.
 *
 * @property string                $id
 * @property string                $po_number
 * @property string                $supplier_id
 * @property string                $warehouse_id
 * @property string                $status
 * @property \Carbon\Carbon|null   $expected_arrival_at
 * @property \Carbon\Carbon|null   $received_at
 * @property int                   $shipping_cost
 * @property int                   $other_costs
 * @property int                   $total_cost
 * @property string|null           $notes
 * @property string                $created_by
 * @property string|null           $approved_by
 * @property \Carbon\Carbon|null   $approved_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 * @property \Carbon\Carbon|null   $deleted_at
 */
class PurchaseOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'purchase_orders';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'po_number',
        'supplier_id',
        'warehouse_id',
        'status',
        'expected_arrival_at',
        'received_at',
        'shipping_cost',
        'other_costs',
        'total_cost',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'expected_arrival_at' => 'date',
        'received_at'         => 'datetime',
        'approved_at'         => 'datetime',
        'shipping_cost'       => 'integer',
        'other_costs'         => 'integer',
        'total_cost'          => 'integer',
    ];

    /**
     * Get the supplier for this purchase order.
     *
     * @return BelongsTo<Supplier, PurchaseOrder>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the destination warehouse for this purchase order.
     *
     * @return BelongsTo<Warehouse, PurchaseOrder>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get all line items on this purchase order.
     *
     * @return HasMany<PurchaseOrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Get all receipts (deliveries) recorded against this purchase order.
     *
     * @return HasMany<PurchaseOrderReceipt>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class, 'purchase_order_id');
    }

    /**
     * Get the staff member who created this purchase order.
     *
     * @return BelongsTo<User, PurchaseOrder>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the staff member who approved this purchase order.
     *
     * @return BelongsTo<User, PurchaseOrder>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
