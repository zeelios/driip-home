<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductVariantLink model representing a many-to-many relationship between products.
 *
 * This pivot model allows products to be linked as variants of each other,
 * enabling products of different colors, materials, or styles to be queried as
 * a variant group. Unlike the old product_variants table, this does not store
 * pricing or inventory - those live directly on the products table.
 *
 * @property string $id
 * @property string $parent_product_id
 * @property string $variant_product_id
 * @property string $relationship_type
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read Product $parentProduct
 * @property-read Product $variantProduct
 */
class ProductVariantLink extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'product_variant_links';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'parent_product_id',
        'variant_product_id',
        'relationship_type',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent product in this variant relationship.
     *
     * @return BelongsTo<Product, ProductVariantLink>
     */
    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    /**
     * Get the variant product in this variant relationship.
     *
     * @return BelongsTo<Product, ProductVariantLink>
     */
    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_product_id');
    }
}
