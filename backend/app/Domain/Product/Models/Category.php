<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * Category model representing a hierarchical product taxonomy node.
 *
 * Categories support one level of parent/child nesting. A category without
 * a parent_id is a top-level (root) category. Soft deletes preserve
 * historical associations.
 *
 * Size options can be assigned to categories, making those sizes available
 * for all products within the category.
 *
 * @property string               $id
 * @property string|null          $parent_id
 * @property string               $name
 * @property string               $slug
 * @property string|null          $description
 * @property string|null          $image
 * @property int                  $sort_order
 * @property bool                 $is_active
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SizeOption> $sizeOptions
 */
class Category extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'categories';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent category of this category.
     *
     * @return BelongsTo<Category, Category>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all direct child categories of this category.
     *
     * @return HasMany<Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all products belonging to this category.
     *
     * @return HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Get size options available for this category.
     *
     * @return BelongsToMany<SizeOption>
     */
    public function sizeOptions(): BelongsToMany
    {
        return $this->belongsToMany(SizeOption::class, 'category_sizes')
            ->withPivot('sort_order')
            ->orderBy('category_sizes.sort_order');
    }

    /**
     * Get available size options for this category.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SizeOption>
     */
    public function availableSizes(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->sizeOptions;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'categories';
    }
}
