<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Maps permissions to roles based on the role hierarchy.
 *
 * super-admin: All permissions (untouchable)
 * admin: All permissions except super-admin modification
 * manager: Full operational access to orders, customers, inventory
 * sales-staff: Own orders only, view customers
 * warehouse-staff: Inventory and packing operations
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Role hierarchy levels for validation.
     */
    protected const ROLE_LEVELS = [
        'warehouse-staff' => 1,
        'sales-staff'     => 2,
        'manager'         => 3,
        'admin'           => 4,
        'super-admin'     => 5,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->assignSuperAdminPermissions();
        $this->assignAdminPermissions();
        $this->assignManagerPermissions();
        $this->assignSalesStaffPermissions();
        $this->assignWarehouseStaffPermissions();
    }

    /**
     * Super-admin: All permissions.
     */
    protected function assignSuperAdminPermissions(): void
    {
        $role = Role::findByName('super-admin', 'web');
        $role->syncPermissions(Permission::all());
    }

    /**
     * Admin: Full access except direct super-admin management.
     */
    protected function assignAdminPermissions(): void
    {
        $role = Role::findByName('admin', 'web');

        $permissions = [
            // Orders - full access
            'orders.view', 'orders.view.own', 'orders.create', 'orders.update',
            'orders.update.own', 'orders.delete', 'orders.confirm', 'orders.pack',
            'orders.ship', 'orders.cancel', 'orders.refund', 'orders.manage',

            // Customers - full access
            'customers.view', 'customers.create', 'customers.update',
            'customers.delete', 'customers.block', 'customers.unblock',
            'customers.merge', 'customers.manage',

            // Staff - full access except can't modify super-admin (enforced in middleware)
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'staff.activate', 'staff.deactivate', 'staff.salary.view',
            'staff.salary.manage', 'staff.assign.roles', 'staff.assign.permissions',
            'staff.manage',

            // Products
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.restore', 'products.publish', 'products.unpublish',
            'products.manage',

            // Product attributes
            'brands.manage', 'categories.manage', 'sizes.manage',
            'colors.manage', 'materials.manage',

            // Inventory
            'inventory.view', 'inventory.adjust', 'inventory.transfer',
            'inventory.export', 'inventory.audit', 'inventory.manage',

            // Purchase orders
            'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.update',
            'purchase-orders.approve', 'purchase-orders.receive', 'purchase-orders.manage',

            // Stock transfers
            'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.update',
            'stock-transfers.approve', 'stock-transfers.ship', 'stock-transfers.receive',
            'stock-transfers.manage',

            // Warehouses
            'warehouses.view', 'warehouses.manage',

            // Coupons
            'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',
            'coupons.activate', 'coupons.deactivate', 'coupons.validate', 'coupons.manage',

            // Shipments
            'shipments.view', 'shipments.create', 'shipments.update',
            'shipments.cancel', 'shipments.track', 'shipments.manage',

            // Courier configs
            'courier-configs.view', 'courier-configs.manage',

            // Remittances
            'remittances.view', 'remittances.record', 'remittances.verify', 'remittances.manage',

            // Payments
            'payments.view', 'payments.record', 'payments.verify', 'payments.refund', 'payments.manage',

            // Bank configs
            'bank-configs.view', 'bank-configs.manage',

            // Pending deposits
            'pending-deposits.view', 'pending-deposits.verify',
            'pending-deposits.reject', 'pending-deposits.manage',

            // Commissions
            'commissions.view', 'commissions.view.own', 'commissions.approve',
            'commissions.pay', 'commissions.manage',

            // Loyalty
            'loyalty.view', 'loyalty.manage', 'loyalty.tiers.manage', 'loyalty.points.adjust',

            // Settings
            'settings.view', 'settings.manage', 'settings.general',
            'settings.notification', 'settings.integration',

            // System
            'dashboard.view', 'reports.view', 'reports.export',
            'activity-logs.view', 'system.backups', 'system.maintenance',
        ];

        $role->syncPermissions($permissions);
    }

    /**
     * Manager: Operational access to orders, customers, inventory.
     * Cannot modify staff or system settings.
     */
    protected function assignManagerPermissions(): void
    {
        $role = Role::findByName('manager', 'web');

        $permissions = [
            // Orders - full operational access
            'orders.view', 'orders.view.own', 'orders.create', 'orders.update',
            'orders.update.own', 'orders.confirm', 'orders.pack',
            'orders.ship', 'orders.cancel', 'orders.refund',

            // Customers - view and block/unblock only
            'customers.view', 'customers.block', 'customers.unblock',

            // Staff - view only (cannot modify)
            'staff.view',

            // Products - view only
            'products.view',

            // Inventory - full access
            'inventory.view', 'inventory.adjust', 'inventory.transfer',
            'inventory.export', 'inventory.audit',

            // Purchase orders
            'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.update',
            'purchase-orders.approve', 'purchase-orders.receive',

            // Stock transfers
            'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.update',
            'stock-transfers.approve', 'stock-transfers.ship', 'stock-transfers.receive',

            // Warehouses - view only
            'warehouses.view',

            // Coupons - view only
            'coupons.view', 'coupons.validate',

            // Shipments
            'shipments.view', 'shipments.create', 'shipments.update', 'shipments.track',

            // Courier configs - view only
            'courier-configs.view',

            // Remittances
            'remittances.view', 'remittances.record', 'remittances.verify',

            // Payments - view and record
            'payments.view', 'payments.record', 'payments.verify',

            // Bank configs - view only
            'bank-configs.view',

            // Pending deposits - verify and reject
            'pending-deposits.view', 'pending-deposits.verify', 'pending-deposits.reject',

            // Commissions - view and approve (cannot pay)
            'commissions.view', 'commissions.view.own', 'commissions.approve',

            // Loyalty - view only
            'loyalty.view',

            // Settings - view only
            'settings.view',

            // System
            'dashboard.view', 'reports.view', 'reports.export',
        ];

        $role->syncPermissions($permissions);
    }

    /**
     * Sales-staff: Own orders only, view customers.
     * Can create orders (assigned to self), update own orders.
     */
    protected function assignSalesStaffPermissions(): void
    {
        $role = Role::findByName('sales-staff', 'web');

        $permissions = [
            // Orders - own only
            'orders.view.own', 'orders.create', 'orders.update.own',

            // Customers - view only (for order creation)
            'customers.view',

            // Products - view only (for order creation)
            'products.view',

            // Inventory - view only
            'inventory.view',

            // Coupons - validate only (for applying discounts)
            'coupons.validate',

            // Payments - record only (for recording deposits)
            'payments.record',

            // Commissions - view own only
            'commissions.view.own',

            // Loyalty - view only
            'loyalty.view',

            // System
            'dashboard.view',
        ];

        $role->syncPermissions($permissions);
    }

    /**
     * Warehouse-staff: Inventory operations and packing.
     */
    protected function assignWarehouseStaffPermissions(): void
    {
        $role = Role::findByName('warehouse-staff', 'web');

        $permissions = [
            // Orders - view and pack only
            'orders.view', 'orders.pack',

            // Products - view only
            'products.view',

            // Inventory - full access
            'inventory.view', 'inventory.adjust', 'inventory.transfer',

            // Stock transfers
            'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.update',
            'stock-transfers.ship', 'stock-transfers.receive',

            // Warehouses - view only
            'warehouses.view',

            // Shipments - create and update
            'shipments.view', 'shipments.create', 'shipments.update', 'shipments.track',

            // System
            'dashboard.view',
        ];

        $role->syncPermissions($permissions);
    }
}
