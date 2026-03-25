<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductPriceHistory model capturing an immutable snapshot of a product's prices
 * at a specific point in time.
 *
 * This table is append-only; records are never updated. There is no updated_at
 * column — changed_at serves as the sole timestamp and is set on creation.
 *
 * @property string               $id
 * @property string               $product_id
 * @property int                  $compare_price
 * @property int                  $cost_price
 * @property int                  $selling_price
 * @property string|null          $changed_by
 * @property string|null          $reason
 * @property \Carbon\Carbon       $changed_at
 */
class ProductPriceHistory extends Model
{
    use HasUuids;

    /**
     * Disable automatic timestamp management — this model is append-only.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * This model has no created_at column; changed_at is used instead.
     *
     * @var string|null
     */
    const CREATED_AT = null;

    /** @var string The table associated with this model. */
    protected $table = 'product_price_history';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'product_id',
        'compare_price',
        'cost_price',
        'selling_price',
        'changed_by',
        'reason',
        'changed_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'compare_price' => 'integer',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the product this price history entry belongs to.
     *
     * @return BelongsTo<Product, ProductPriceHistory>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the staff user who made this price change.
     *
     * @return BelongsTo<User, ProductPriceHistory>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
