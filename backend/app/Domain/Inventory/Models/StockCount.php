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

/**
 * StockCount model representing a scheduled or ad-hoc physical inventory count task.
 *
 * Tracks the full count lifecycle from scheduling through approval.
 * Variance totals are calculated when the count is completed and
 * count_correction inventory transactions are created on approval.
 *
 * @property string                $id
 * @property string                $count_number
 * @property string                $warehouse_id
 * @property string                $type
 * @property string                $status
 * @property \Carbon\Carbon|null   $scheduled_at
 * @property \Carbon\Carbon|null   $started_at
 * @property \Carbon\Carbon|null   $completed_at
 * @property string|null           $approved_by
 * @property \Carbon\Carbon|null   $approved_at
 * @property int|null              $total_variance_qty
 * @property int|null              $total_variance_value
 * @property string|null           $notes
 * @property string                $created_by
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class StockCount extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'stock_counts';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'count_number',
        'warehouse_id',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'approved_by',
        'approved_at',
        'total_variance_qty',
        'total_variance_value',
        'notes',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'scheduled_at'         => 'date',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
        'approved_at'          => 'datetime',
        'total_variance_qty'   => 'integer',
        'total_variance_value' => 'integer',
    ];

    /**
     * Get the warehouse where this count is taking place.
     *
     * @return BelongsTo<Warehouse, StockCount>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get all count item lines for this stock count.
     *
     * @return HasMany<StockCountItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'stock_count_id');
    }

    /**
     * Get the staff member who created this count task.
     *
     * @return BelongsTo<User, StockCount>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the staff member who approved this count.
     *
     * @return BelongsTo<User, StockCount>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
