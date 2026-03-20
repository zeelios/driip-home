<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WarehouseStaff model representing the assignment of a staff member to a warehouse.
 *
 * Records the role, the date the assignment started (assigned_at), and optionally
 * the date it ended (unassigned_at) when the staff member leaves the warehouse.
 *
 * @property string                $id
 * @property string                $warehouse_id
 * @property string                $user_id
 * @property string                $role
 * @property \Carbon\Carbon        $assigned_at
 * @property \Carbon\Carbon|null   $unassigned_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class WarehouseStaff extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'warehouse_staff';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'warehouse_id',
        'user_id',
        'role',
        'assigned_at',
        'unassigned_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'assigned_at'   => 'date',
        'unassigned_at' => 'date',
    ];

    /**
     * Get the warehouse this assignment belongs to.
     *
     * @return BelongsTo<Warehouse, WarehouseStaff>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the staff user who is assigned.
     *
     * @return BelongsTo<User, WarehouseStaff>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
