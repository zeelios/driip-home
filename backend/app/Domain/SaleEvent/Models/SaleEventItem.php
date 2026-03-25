<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Models;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleEventItem model representing one product's participation in a sale event.
 *
 * Stores the discounted sale_price for the product during the event window,
 * optional per-customer and global quantity caps, and a sold_count counter.
 *
 * @property string               $id
 * @property string               $sale_event_id
 * @property string               $product_id
 * @property int                  $sale_price
 * @property int|null             $compare_price
 * @property int|null             $max_qty_per_customer
 * @property int|null             $max_qty_total
 * @property int                  $sold_count
 * @property bool                 $is_active
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class SaleEventItem extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'sale_event_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'sale_event_id',
        'product_id',
        'sale_price',
        'compare_price',
        'max_qty_per_customer',
        'max_qty_total',
        'sold_count',
        'is_active',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'sale_price' => 'integer',
        'compare_price' => 'integer',
        'max_qty_per_customer' => 'integer',
        'max_qty_total' => 'integer',
        'sold_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the sale event this item belongs to.
     *
     * @return BelongsTo<SaleEvent, SaleEventItem>
     */
    public function saleEvent(): BelongsTo
    {
        return $this->belongsTo(SaleEvent::class, 'sale_event_id');
    }

    /**
     * Get the product this item refers to.
     *
     * @return BelongsTo<Product, SaleEventItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
