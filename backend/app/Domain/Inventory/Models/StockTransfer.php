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
 * StockTransfer model representing an inter-warehouse stock movement request.
 *
 * Tracks the transfer lifecycle: draft → approved → dispatched → received.
 * Inventory transactions (transfer_out and transfer_in) are created when
 * the transfer is dispatched and received respectively.
 *
 * @property string                $id
 * @property string                $transfer_number
 * @property string                $from_warehouse_id
 * @property string                $to_warehouse_id
 * @property string                $status
 * @property string|null           $reason
 * @property string                $requested_by
 * @property string|null           $approved_by
 * @property \Carbon\Carbon|null   $dispatched_at
 * @property \Carbon\Carbon|null   $received_at
 * @property string|null           $notes
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 * @property \Carbon\Carbon|null   $deleted_at
 */
class StockTransfer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'stock_transfers';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'dispatched_at',
        'received_at',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at'   => 'datetime',
    ];

    /**
     * Get the source warehouse for this transfer.
     *
     * @return BelongsTo<Warehouse, StockTransfer>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse for this transfer.
     *
     * @return BelongsTo<Warehouse, StockTransfer>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get all line items on this stock transfer.
     *
     * @return HasMany<StockTransferItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    /**
     * Get the staff member who requested this transfer.
     *
     * @return BelongsTo<User, StockTransfer>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the staff member who approved this transfer.
     *
     * @return BelongsTo<User, StockTransfer>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
