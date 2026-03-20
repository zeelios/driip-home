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
 * Product model representing a top-level catalogue item in Driip.
 *
 * A product groups one or more variants (e.g. different sizes/colours) and
 * carries shared metadata such as brand, category, gender, season, and SEO
 * fields. Actual pricing and inventory live on ProductVariant.
 *
 * @property string               $id
 * @property string|null          $brand_id
 * @property string|null          $category_id
 * @property string               $name
 * @property string               $slug
 * @property string|null          $description
 * @property string|null          $short_description
 * @property string|null          $sku_base
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
        'sku_base',
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
        'tags'         => 'array',
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
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
     * Get all variants for this product.
     *
     * @return HasMany<ProductVariant>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
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
