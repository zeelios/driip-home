<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrderReceipt model representing a goods-received note for a purchase order.
 *
 * Records the physical receipt of stock into a warehouse, including who received it,
 * when, any attached documents, and a breakdown of items received with their condition.
 *
 * @property string                  $id
 * @property string                  $purchase_order_id
 * @property string                  $receipt_number
 * @property string                  $received_by
 * @property \Carbon\Carbon          $received_at
 * @property string|null             $notes
 * @property array<int,mixed>        $attachments
 * @property array<int,mixed>        $receipt_items
 * @property \Carbon\Carbon|null     $created_at
 * @property \Carbon\Carbon|null     $updated_at
 */
class PurchaseOrderReceipt extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'purchase_order_receipts';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'purchase_order_id',
        'receipt_number',
        'received_by',
        'received_at',
        'notes',
        'attachments',
        'receipt_items',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'received_at'  => 'datetime',
        'attachments'  => 'array',
        'receipt_items' => 'array',
    ];

    /**
     * Get the purchase order this receipt belongs to.
     *
     * @return BelongsTo<PurchaseOrder, PurchaseOrderReceipt>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the staff user who received the goods.
     *
     * @return BelongsTo<User, PurchaseOrderReceipt>
     */
    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
