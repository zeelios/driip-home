<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryTransaction model representing a single stock movement event.
 *
 * Every change to inventory quantities (receive, adjustment, reserve, release,
 * transfer_in, transfer_out, count_correction) is recorded as an immutable
 * transaction with before/after quantities for full auditability.
 * Only has a created_at timestamp — records are never updated.
 *
 * @property string               $id
 * @property string               $product_variant_id
 * @property string               $warehouse_id
 * @property string               $type
 * @property int                  $quantity
 * @property int                  $quantity_before
 * @property int                  $quantity_after
 * @property int|null             $unit_cost
 * @property string|null          $lot_number
 * @property string|null          $reference_type
 * @property string|null          $reference_id
 * @property string|null          $notes
 * @property string|null          $created_by
 * @property \Carbon\Carbon       $created_at
 */
class InventoryTransaction extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'inventory_transactions';

    /**
     * Disable automatic timestamp management.
     *
     * Only created_at is tracked, set on insert.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The column used as the creation timestamp.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'lot_number',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
        'unit_cost'       => 'integer',
        'created_at'      => 'datetime',
    ];

    /**
     * Get the product variant this transaction is for.
     *
     * @return BelongsTo<ProductVariant, InventoryTransaction>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the warehouse where the stock movement occurred.
     *
     * @return BelongsTo<Warehouse, InventoryTransaction>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the staff user who created this transaction.
     *
     * @return BelongsTo<User, InventoryTransaction>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
