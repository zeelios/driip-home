<?php

declare(strict_types=1);

namespace App\Domain\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shipping or billing address associated with a customer.
 *
 * A customer may have multiple addresses; one can be marked as the default.
 * Each address captures full Vietnamese address hierarchy (province, district, ward).
 *
 * @property string      $id
 * @property string      $customer_id
 * @property string|null $label
 * @property string|null $recipient_name
 * @property string|null $phone
 * @property string|null $province
 * @property string|null $district
 * @property string|null $ward
 * @property string|null $address
 * @property string|null $zip_code
 * @property bool        $is_default
 */
class CustomerAddress extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'customer_addresses';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'customer_id',
        'label',
        'recipient_name',
        'phone',
        'province',
        'district',
        'ward',
        'address',
        'zip_code',
        'is_default',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the customer who owns this address.
     *
     * @return BelongsTo<Customer, CustomerAddress>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
