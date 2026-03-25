<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * SizeOption model representing available sizing options (S, M, L, 42, etc.)
 *
 * Size options are assigned to categories and then products can select
 * from the available sizes for their category.
 *
 * @property string $id
 * @property string $code
 * @property string $display_name
 * @property string $size_type
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SizeOption extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'size_options';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'code',
        'display_name',
        'size_type',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get categories that have this size option available.
     *
     * @return BelongsToMany<Category>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_sizes')
            ->withPivot('sort_order')
            ->orderBy('category_sizes.sort_order');
    }

    /**
     * Get products that use this size option.
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sizes')
            ->withPivot('sku_suffix', 'sort_order')
            ->orderBy('product_sizes.sort_order');
    }
}
