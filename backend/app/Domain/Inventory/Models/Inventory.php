<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Inventory model representing the stock level of a product in a warehouse.
 *
 * Tracks on-hand, reserved, available, and incoming quantities along with
 * reorder thresholds. Only has an updated_at timestamp (no created_at).
 * The syncAvailable() method recalculates quantity_available from on-hand minus reserved.
 *
 * @property string               $id
 * @property string               $product_id
 * @property string               $warehouse_id
 * @property int                  $quantity_on_hand
 * @property int                  $quantity_reserved
 * @property int                  $quantity_available
 * @property int                  $quantity_incoming
 * @property int|null             $reorder_point
 * @property int|null             $reorder_quantity
 * @property \Carbon\Carbon|null  $last_counted_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class Inventory extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'inventory';

    /**
     * Disable automatic timestamp management.
     *
     * This model only tracks updated_at, which is managed manually.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> Date-castable columns for Carbon instances. */
    protected $dates = ['updated_at', 'last_counted_at'];

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'quantity_incoming',
        'reorder_point',
        'reorder_quantity',
        'last_counted_at',
        'updated_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_available' => 'integer',
        'quantity_incoming' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'last_counted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product variant this inventory record belongs to.
     *
     * @return BelongsTo<ProductVariant, Inventory>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the product this inventory record belongs to (through variant).
     *
     * @return BelongsTo<Product, Inventory>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the warehouse where this inventory is held.
     *
     * @return BelongsTo<Warehouse, Inventory>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Recalculate and persist quantity_available.
     *
     * Sets quantity_available = quantity_on_hand − quantity_reserved, then saves.
     * Call this after any direct mutation of on-hand or reserved quantities.
     *
     * @return void
     */
    public function syncAvailable(): void
    {
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->updated_at = now();
        $this->save();
    }
}
