<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Product\Models\SizeOption;
use App\Domain\Shipment\Models\Shipment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderItem model representing a single physical item within an order.
 *
 * Each row represents one physical product instance, allowing individual
 * tracking of inventory allocation, shipment assignment, and return status.
 *
 * @property string                $id
 * @property string                $order_id
 * @property string|null           $product_id
 * @property string                $sku
 * @property string                $name
 * @property string|null           $size_option_id
 * @property string|null           $color
 * @property int                   $unit_price
 * @property int                   $cost_price
 * @property int                   $discount_amount
 * @property string|null           $inventory_id
 * @property string|null           $shipment_id
 * @property string                $status
 * @property \Carbon\Carbon|null   $picked_at
 * @property string|null           $picked_by
 * @property \Carbon\Carbon|null   $packed_at
 * @property string|null           $packed_by
 * @property \Carbon\Carbon|null   $returned_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 *
 * @property-read SizeOption|null  $sizeOption
 * @property-read Inventory|null   $inventory
 * @property-read Shipment|null    $shipment
 * @property-read \App\Domain\User\Models\User|null $pickedBy
 * @property-read \App\Domain\User\Models\User|null $packedBy
 */
class OrderItem extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'order_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'name',
        'size_option_id',
        'color',
        'unit_price',
        'cost_price',
        'discount_amount',
        'inventory_id',
        'shipment_id',
        'status',
        'picked_at',
        'picked_by',
        'packed_at',
        'packed_by',
        'returned_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'unit_price' => 'integer',
        'cost_price' => 'integer',
        'discount_amount' => 'integer',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
        'returned_at' => 'datetime',
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
     * Get the product this item was created from.
     *
     * Returns null if the product has since been deleted.
     *
     * @return BelongsTo<\App\Domain\Product\Models\Product, OrderItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Product\Models\Product::class, 'product_id');
    }

    /**
     * Get the size option for this item.
     *
     * @return BelongsTo<SizeOption, OrderItem>
     */
    public function sizeOption(): BelongsTo
    {
        return $this->belongsTo(SizeOption::class, 'size_option_id');
    }

    /**
     * Get the inventory record allocated to this item.
     *
     * @return BelongsTo<Inventory, OrderItem>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Get the shipment this item is assigned to.
     *
     * @return BelongsTo<Shipment, OrderItem>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Get the user who picked this item.
     *
     * @return BelongsTo<\App\Domain\User\Models\User, OrderItem>
     */
    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'picked_by');
    }

    /**
     * Get the user who packed this item.
     *
     * @return BelongsTo<\App\Domain\User\Models\User, OrderItem>
     */
    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'packed_by');
    }
}
