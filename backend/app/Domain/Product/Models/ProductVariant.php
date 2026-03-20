<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductVariant model representing a specific purchasable SKU of a product.
 *
 * Each variant stores its own pricing, weight, and attribute combination
 * (e.g. Size=M + Colour=Black). During an active sale event, sale_price
 * overrides the standard selling_price. Inventory is tracked per-warehouse
 * via Inventory records.
 *
 * @property string                $id
 * @property string                $product_id
 * @property string                $sku
 * @property string|null           $barcode
 * @property array<string,string>  $attribute_values
 * @property int                   $compare_price
 * @property int                   $cost_price
 * @property int                   $selling_price
 * @property int|null              $sale_price
 * @property string|null           $sale_event_id
 * @property int                   $weight_grams
 * @property string                $status
 * @property int                   $sort_order
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 * @property \Carbon\Carbon|null   $deleted_at
 */
class ProductVariant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'product_variants';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'attribute_values',
        'compare_price',
        'cost_price',
        'selling_price',
        'sale_price',
        'sale_event_id',
        'weight_grams',
        'status',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'attribute_values' => 'array',
        'sale_price'       => 'integer',
        'compare_price'    => 'integer',
        'cost_price'       => 'integer',
        'selling_price'    => 'integer',
        'weight_grams'     => 'integer',
        'sort_order'       => 'integer',
    ];

    /**
     * Get the parent product this variant belongs to.
     *
     * @return BelongsTo<Product, ProductVariant>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get all inventory records for this variant across all warehouses.
     *
     * @return HasMany<\App\Domain\Inventory\Models\Inventory>
     */
    public function inventory(): HasMany
    {
        return $this->hasMany('App\Domain\Inventory\Models\Inventory', 'product_variant_id');
    }

    /**
     * Get the price history entries for this variant.
     *
     * @return HasMany<ProductPriceHistory>
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_variant_id');
    }

    /**
     * Return the currently applicable selling price for this variant.
     *
     * If an active sale_price is set (i.e. a flash sale is in progress),
     * it takes precedence over the standard selling_price.
     *
     * @return int
     */
    public function effectivePrice(): int
    {
        return $this->sale_price ?? $this->selling_price;
    }

    /**
     * Scope a query to only include active variants.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
