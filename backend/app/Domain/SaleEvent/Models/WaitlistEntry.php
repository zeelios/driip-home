<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * WaitlistEntry model recording a customer's or guest's interest in a
 * specific product or variant (typically before a drop launch or restock).
 *
 * This model is insert-only: there is no updated_at column. The created_at
 * column is managed manually (nullable in migration, set on creation).
 * Notification delivery is tracked via the notified_at timestamp.
 *
 * @property string               $id
 * @property string               $product_id
 * @property string|null          $product_variant_id
 * @property string|null          $customer_id
 * @property string|null          $email
 * @property string|null          $phone
 * @property string|null          $source
 * @property \Carbon\Carbon|null  $notified_at
 * @property \Carbon\Carbon|null  $created_at
 */
class WaitlistEntry extends Model
{
    use HasUuids;

    /**
     * Disable automatic timestamp management.
     *
     * created_at is set explicitly on creation; there is no updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The column used as the model's creation timestamp.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * This model has no updated_at column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /** @var string The table associated with this model. */
    protected $table = 'waitlist_entries';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'customer_id',
        'email',
        'phone',
        'source',
        'notified_at',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'notified_at' => 'datetime',
        'created_at'  => 'datetime',
    ];
}
