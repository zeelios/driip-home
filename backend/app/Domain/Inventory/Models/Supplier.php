<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * Supplier model representing a vendor who supplies products to Driip.
 *
 * Tracks contact details, payment terms, and the supplier's active status.
 * All associated purchase orders are accessible via the purchaseOrders relation.
 *
 * @property string               $id
 * @property string               $code
 * @property string               $name
 * @property string|null          $contact_name
 * @property string|null          $email
 * @property string|null          $phone
 * @property string|null          $address
 * @property string|null          $province
 * @property string|null          $country
 * @property string|null          $payment_terms
 * @property string|null          $notes
 * @property bool                 $is_active
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 */
class Supplier extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'suppliers';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'province',
        'country',
        'payment_terms',
        'notes',
        'is_active',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'suppliers';
    }
}
