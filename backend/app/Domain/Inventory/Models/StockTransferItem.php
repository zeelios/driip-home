<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockTransferItem model representing a single product line on a stock transfer.
 *
 * Tracks how many units were requested, how many were actually dispatched,
 * and how many arrived at the destination warehouse.
 *
 * @property string       $id
 * @property string       $stock_transfer_id
 * @property string       $product_id
 * @property int          $quantity_requested
 * @property int          $quantity_dispatched
 * @property int          $quantity_received
 * @property string|null  $notes
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class StockTransferItem extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'stock_transfer_items';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity_requested',
        'quantity_dispatched',
        'quantity_received',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_dispatched' => 'integer',
        'quantity_received' => 'integer',
    ];

    /**
     * Get the parent stock transfer.
     *
     * @return BelongsTo<StockTransfer, StockTransferItem>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    /**
     * Get the product being transferred.
     *
     * @return BelongsTo<Product, StockTransferItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
