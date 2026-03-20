<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderItem model representing a single line item within an order.
 *
 * Snapshots the product variant's key attributes (sku, name, size, color,
 * pricing) at the time of purchase so the record remains accurate even if
 * the underlying product is later modified or deleted.
 *
 * @property string      $id
 * @property string      $order_id
 * @property string|null $product_variant_id
 * @property string      $sku
 * @property string      $name
 * @property string|null $size
 * @property string|null $color
 * @property int         $unit_price
 * @property int         $cost_price
 * @property int         $quantity
 * @property int         $quantity_returned
 * @property int         $discount_amount
 * @property int         $total_price
 */
class OrderItem extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'sku',
        'name',
        'size',
        'color',
        'unit_price',
        'cost_price',
        'quantity',
        'quantity_returned',
        'discount_amount',
        'total_price',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'unit_price'        => 'integer',
        'cost_price'        => 'integer',
        'total_price'       => 'integer',
        'discount_amount'   => 'integer',
        'quantity'          => 'integer',
        'quantity_returned' => 'integer',
    ];

    /**
     * Get the order that this item belongs to.
     *
     * @return BelongsTo<Order, OrderItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the product variant this item was created from.
     *
     * Returns null if the variant has since been deleted.
     *
     * @return BelongsTo<\App\Domain\Product\Models\ProductVariant, OrderItem>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo('App\Domain\Product\Models\ProductVariant', 'product_variant_id');
    }
}
