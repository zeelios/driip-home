<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductVariant model representing a specific variant of a product.
 *
 * Variants have unique SKUs and can have different attribute combinations
 * (size, color, etc.) and pricing from the parent product.
 *
 * @property string $id
 * @property string $product_id
 * @property string $sku
 * @property string|null $barcode
 * @property array $attribute_values
 * @property int $compare_price
 * @property int $cost_price
 * @property int $selling_price
 * @property int|null $sale_price
 * @property string|null $sale_event_id
 * @property int $weight_grams
 * @property string $status
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ProductVariant extends Model
{
    use HasUuids, SoftDeletes;

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
        'compare_price' => 'integer',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
        'sale_price' => 'integer',
        'weight_grams' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product this variant belongs to.
     *
     * @return BelongsTo<Product, ProductVariant>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the sale event associated with this variant (if any).
     *
     * @return BelongsTo<SaleEvent, ProductVariant>
     */
    public function saleEvent(): BelongsTo
    {
        return $this->belongsTo(SaleEvent::class, 'sale_event_id');
    }
}
