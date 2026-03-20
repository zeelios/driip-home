<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockCountItem model representing a single variant line in a stock count task.
 *
 * Records the expected quantity (from system), the physically counted quantity,
 * the resulting variance, its monetary value, and who performed the count.
 *
 * @property string                $id
 * @property string                $stock_count_id
 * @property string                $product_variant_id
 * @property int                   $quantity_expected
 * @property int|null              $quantity_counted
 * @property int|null              $variance
 * @property int|null              $variance_value
 * @property string|null           $notes
 * @property string|null           $counted_by
 * @property \Carbon\Carbon|null   $counted_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class StockCountItem extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'stock_count_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'stock_count_id',
        'product_variant_id',
        'quantity_expected',
        'quantity_counted',
        'variance',
        'variance_value',
        'notes',
        'counted_by',
        'counted_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'quantity_expected' => 'integer',
        'quantity_counted'  => 'integer',
        'variance'          => 'integer',
        'variance_value'    => 'integer',
        'counted_at'        => 'datetime',
    ];

    /**
     * Get the parent stock count task.
     *
     * @return BelongsTo<StockCount, StockCountItem>
     */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    /**
     * Get the product variant being counted.
     *
     * @return BelongsTo<ProductVariant, StockCountItem>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the staff member who performed the physical count for this item.
     *
     * @return BelongsTo<User, StockCountItem>
     */
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
