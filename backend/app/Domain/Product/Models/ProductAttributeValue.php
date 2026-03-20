<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductAttributeValue model representing a single option for a product attribute.
 *
 * Examples: Size → "M", Colour → "Black" (with optional hex swatch).
 * Values are ordered via sort_order within their parent attribute.
 *
 * @property string               $id
 * @property string               $attribute_id
 * @property string               $value
 * @property string|null          $color_hex
 * @property int                  $sort_order
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class ProductAttributeValue extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'product_attribute_values';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'attribute_id',
        'value',
        'color_hex',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent attribute this value belongs to.
     *
     * @return BelongsTo<ProductAttribute, ProductAttributeValue>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }
}
