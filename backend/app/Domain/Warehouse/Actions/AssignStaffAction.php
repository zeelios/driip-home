<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Actions;

use App\Domain\Warehouse\Models\Warehouse;
use App\Domain\Warehouse\Models\WarehouseStaff;

/**
 * Action: assign or update a staff member's role in a warehouse.
 *
 * Uses upsert logic: if the staff member is already assigned to the warehouse,
 * their role is updated; otherwise a new assignment is created.
 */
class AssignStaffAction
{
    /**
     * Execute the staff assignment for a warehouse.
     *
     * @param  Warehouse  $warehouse  The warehouse to assign staff to.
     * @param  string     $userId     UUID of the staff user to assign.
     * @param  string     $role       The role to assign (e.g. 'staff', 'manager').
     *
     * @return WarehouseStaff  The created or updated warehouse staff record.
     */
    public function execute(Warehouse $warehouse, string $userId, string $role): WarehouseStaff
    {
        /** @var WarehouseStaff $assignment */
        $assignment = WarehouseStaff::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'user_id'      => $userId,
            ],
            [
                'role'        => $role,
                'assigned_at' => now(),
            ]
        );

        return $assignment;
    }
}
