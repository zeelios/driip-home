<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Models;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse model representing a physical or virtual storage location.
 *
 * A warehouse can be of four types: main, satellite, virtual, or consignment.
 * It has an optional manager (a staff User), can have multiple staff assignments,
 * and holds inventory records for all product variants stored there.
 *
 * @property string               $id
 * @property string               $code
 * @property string               $name
 * @property string               $type
 * @property string|null          $address
 * @property string|null          $province
 * @property string|null          $district
 * @property string|null          $phone
 * @property string|null          $manager_id
 * @property bool                 $is_active
 * @property string|null          $notes
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class Warehouse extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'warehouses';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'province',
        'district',
        'phone',
        'manager_id',
        'is_active',
        'notes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the manager (staff user) responsible for this warehouse.
     *
     * @return BelongsTo<User, Warehouse>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all staff assignments for this warehouse.
     *
     * @return HasMany<WarehouseStaff>
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(WarehouseStaff::class, 'warehouse_id');
    }

    /**
     * Get all inventory records stored in this warehouse.
     *
     * @return HasMany<Inventory>
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }
}
