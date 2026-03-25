<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product model representing a top-level catalogue item in Driip.
 *
 * A product now carries its own SKU, pricing, and weight. It can be linked to
 * other products as variants (e.g., different colors) via the
 * product_variant_links table. Size options are selected from the category's
 * available sizes.
 *
 * @property string               $id
 * @property string|null          $brand_id
 * @property string|null          $category_id
 * @property string               $name
 * @property string               $slug
 * @property string|null          $description
 * @property string|null          $short_description
 * @property string|null          $sku
 * @property string|null          $barcode
 * @property int                  $compare_price
 * @property int                  $cost_price
 * @property int                  $selling_price
 * @property int|null             $sale_price
 * @property int|null             $weight_grams
 * @property string|null          $sale_event_id
 * @property string|null          $gender
 * @property string|null          $season
 * @property array<int,string>    $tags
 * @property string               $status
 * @property bool                 $is_featured
 * @property \Carbon\Carbon|null  $published_at
 * @property string|null          $meta_title
 * @property string|null          $meta_description
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SizeOption> $sizeOptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $variantPeers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $parentVariants
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'products';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'barcode',
        'compare_price',
        'cost_price',
        'selling_price',
        'sale_price',
        'weight_grams',
        'sale_event_id',
        'gender',
        'season',
        'tags',
        'status',
        'is_featured',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'compare_price' => 'integer',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
        'sale_price' => 'integer',
        'weight_grams' => 'integer',
    ];

    /**
     * Get the brand this product belongs to.
     *
     * @return BelongsTo<Brand, Product>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the category this product belongs to.
     *
     * @return BelongsTo<Category, Product>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get size options for this product.
     *
     * @return BelongsToMany<SizeOption>
     */
    public function sizeOptions(): BelongsToMany
    {
        return $this->belongsToMany(SizeOption::class, 'product_sizes')
            ->withPivot('sku_suffix', 'sort_order')
            ->orderBy('product_sizes.sort_order');
    }

    /**
     * Get products that are variants of this product (e.g., different colors).
     *
     * @return BelongsToMany<Product>
     */
    public function variantPeers(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_variant_links',
            'parent_product_id',
            'variant_product_id'
        )->withPivot('relationship_type', 'sort_order');
    }

    /**
     * Get products that list this product as their variant.
     *
     * @return BelongsToMany<Product>
     */
    public function parentVariants(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_variant_links',
            'variant_product_id',
            'parent_product_id'
        )->withPivot('relationship_type', 'sort_order');
    }

    /**
     * Get all inventory records for this product across all warehouses.
     *
     * @return HasMany<\App\Domain\Inventory\Models\Inventory>
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(\App\Domain\Inventory\Models\Inventory::class, 'product_id');
    }

    /**
     * Return the currently applicable selling price for this product.
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
     * Get all related variants (bidirectional).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function allVariants(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->variantPeers->merge($this->parentVariants);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
