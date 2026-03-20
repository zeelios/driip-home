<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Brand model representing a fashion/product brand within the Driip catalogue.
 *
 * Brands are used to group products under a single label and can be toggled
 * active/inactive. A soft-deleted brand retains its historical associations
 * with products.
 *
 * @property string               $id
 * @property string               $name
 * @property string               $slug
 * @property string|null          $logo
 * @property string|null          $description
 * @property bool                 $is_active
 * @property int                  $sort_order
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 */
class Brand extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'brands';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'is_active',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all products belonging to this brand.
     *
     * @return HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
