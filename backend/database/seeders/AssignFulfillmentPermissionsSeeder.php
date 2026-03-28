<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Assigns fulfillment and purchase request permissions to roles.
 */
class AssignFulfillmentPermissionsSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        // Fulfillment permissions
        $fulfillmentPerms = [
            'fulfillment.view',
            'fulfillment.pick',
            'fulfillment.pack',
            'fulfillment.export',
            'fulfillment.manage',
        ];

        // Purchase request permissions
        $purchaseRequestPerms = [
            'purchase_request.view',
            'purchase_request.create',
            'purchase_request.manage',
        ];

        // Get roles
        $superAdmin = Role::findByName('super-admin', 'web');
        $admin = Role::findByName('admin', 'web');
        $manager = Role::findByName('manager', 'web');
        $warehouseStaff = Role::findByName('warehouse-staff', 'web');

        // Assign to super-admin (all permissions)
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_merge($fulfillmentPerms, $purchaseRequestPerms));
        }

        // Assign to admin (all permissions)
        if ($admin) {
            $admin->givePermissionTo(array_merge($fulfillmentPerms, $purchaseRequestPerms));
        }

        // Assign to manager (view + manage)
        if ($manager) {
            $manager->givePermissionTo([
                'fulfillment.view',
                'fulfillment.export',
                'fulfillment.manage',
                'purchase_request.view',
                'purchase_request.create',
                'purchase_request.manage',
            ]);
        }

        // Assign to warehouse-staff (operational permissions)
        if ($warehouseStaff) {
            $warehouseStaff->givePermissionTo([
                'fulfillment.view',
                'fulfillment.pick',
                'fulfillment.pack',
                'purchase_request.view',
            ]);
        }

        $this->command->info('Fulfillment and purchase request permissions assigned successfully.');
    }
}
