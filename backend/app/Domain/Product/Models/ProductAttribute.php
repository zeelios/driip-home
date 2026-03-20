<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProductAttribute model representing a configurable product dimension (e.g. Size, Colour).
 *
 * Each attribute has many values (e.g. Size → S, M, L, XL). Attributes are
 * shared across all products in the catalogue.
 *
 * @property string               $id
 * @property string               $name
 * @property int                  $sort_order
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class ProductAttribute extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'product_attributes';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get all values belonging to this attribute.
     *
     * @return HasMany<ProductAttributeValue>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }
}
