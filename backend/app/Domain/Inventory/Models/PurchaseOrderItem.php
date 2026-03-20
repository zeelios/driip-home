<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrderItem model representing a single line item on a purchase order.
 *
 * Tracks the ordered quantity, received quantity, and unit/total cost for
 * a specific product variant SKU on a given purchase order.
 *
 * @property string       $id
 * @property string       $purchase_order_id
 * @property string       $product_variant_id
 * @property string       $sku
 * @property int          $quantity_ordered
 * @property int          $quantity_received
 * @property int          $unit_cost
 * @property int          $total_cost
 * @property string|null  $notes
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class PurchaseOrderItem extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'purchase_order_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'sku',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_cost'         => 'integer',
        'total_cost'        => 'integer',
    ];

    /**
     * Get the purchase order this item belongs to.
     *
     * @return BelongsTo<PurchaseOrder, PurchaseOrderItem>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the product variant for this line item.
     *
     * @return BelongsTo<ProductVariant, PurchaseOrderItem>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
