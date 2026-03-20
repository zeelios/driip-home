<?php

declare(strict_types=1);

namespace App\Domain\Tax\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax invoice record associated with an order.
 *
 * Immutable after creation. PDF generation is deferred — file_url will be null
 * until a background job populates it. Buyer fields are optional to support
 * both B2C (no tax code) and B2B (with tax code) invoice flows.
 *
 * @property string                $id
 * @property string                $order_id
 * @property string                $invoice_number
 * @property string                $invoice_type
 * @property string|null           $buyer_name
 * @property string|null           $buyer_tax_code
 * @property string|null           $buyer_address
 * @property \Carbon\Carbon|null   $issued_at
 * @property string|null           $file_url
 * @property string|null           $created_by
 * @property \Carbon\Carbon        $created_at
 */
class TaxInvoice extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'tax_invoices';

    /**
     * Disable automatic timestamp management; only created_at is stored.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'order_id',
        'invoice_number',
        'invoice_type',
        'buyer_name',
        'buyer_tax_code',
        'buyer_address',
        'issued_at',
        'file_url',
        'created_by',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'issued_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the order this invoice was issued for.
     *
     * Uses a string class reference to avoid circular dependency with the
     * Order domain.
     *
     * @return BelongsTo<\Illuminate\Database\Eloquent\Model, TaxInvoice>
     */
    public function order(): BelongsTo
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $orderClass */
        $orderClass = 'App\Domain\Order\Models\Order';
        return $this->belongsTo($orderClass, 'order_id');
    }

    /**
     * Get the staff member who generated this invoice.
     *
     * @return BelongsTo<User, TaxInvoice>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
