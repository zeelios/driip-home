<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seeds all granular permissions for the Driip permission system.
 *
 * Organized by domain with CRUD and custom action permissions.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }

    /**
     * Get all permissions organized by domain.
     *
     * @return array<string>
     */
    protected function getPermissions(): array
    {
        return array_merge(
            $this->getOrderPermissions(),
            $this->getCustomerPermissions(),
            $this->getStaffPermissions(),
            $this->getProductPermissions(),
            $this->getInventoryPermissions(),
            $this->getCouponPermissions(),
            $this->getShipmentPermissions(),
            $this->getPaymentPermissions(),
            $this->getCommissionPermissions(),
            $this->getLoyaltyPermissions(),
            $this->getSettingsPermissions(),
            $this->getFulfillmentPermissions(),
            $this->getPurchaseRequestPermissions(),
            $this->getSystemPermissions(),
        );
    }

    /**
     * Order domain permissions.
     *
     * @return array<string>
     */
    protected function getOrderPermissions(): array
    {
        return [
            // View permissions (all vs own)
            'orders.view',
            'orders.view.own',

            // CRUD
            'orders.create',
            'orders.update',
            'orders.update.own',
            'orders.delete',

            // Order lifecycle actions
            'orders.confirm',
            'orders.pack',
            'orders.ship',
            'orders.cancel',
            'orders.refund',

            // Bulk/coarse permission
            'orders.manage',
        ];
    }

    /**
     * Customer domain permissions.
     *
     * @return array<string>
     */
    protected function getCustomerPermissions(): array
    {
        return [
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.block',
            'customers.unblock',
            'customers.merge',
            'customers.manage',
        ];
    }

    /**
     * Staff domain permissions.
     *
     * @return array<string>
     */
    protected function getStaffPermissions(): array
    {
        return [
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.activate',
            'staff.deactivate',

            // Salary management
            'staff.salary.view',
            'staff.salary.manage',

            // Role assignment
            'staff.assign.roles',
            'staff.assign.permissions',

            'staff.manage',
        ];
    }

    /**
     * Product domain permissions.
     *
     * @return array<string>
     */
    protected function getProductPermissions(): array
    {
        return [
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.restore',
            'products.publish',
            'products.unpublish',
            'products.manage',

            // Product attributes
            'brands.manage',
            'categories.manage',
            'sizes.manage',
            'colors.manage',
            'materials.manage',
        ];
    }

    /**
     * Inventory domain permissions.
     *
     * @return array<string>
     */
    protected function getInventoryPermissions(): array
    {
        return [
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.export',
            'inventory.audit',
            'inventory.manage',

            // Purchase orders
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.update',
            'purchase-orders.approve',
            'purchase-orders.receive',
            'purchase-orders.manage',

            // Stock transfers
            'stock-transfers.view',
            'stock-transfers.create',
            'stock-transfers.update',
            'stock-transfers.approve',
            'stock-transfers.ship',
            'stock-transfers.receive',
            'stock-transfers.manage',

            // Warehouses
            'warehouses.view',
            'warehouses.manage',
        ];
    }

    /**
     * Coupon domain permissions.
     *
     * @return array<string>
     */
    protected function getCouponPermissions(): array
    {
        return [
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',
            'coupons.activate',
            'coupons.deactivate',
            'coupons.validate',
            'coupons.manage',
        ];
    }

    /**
     * Shipment domain permissions.
     *
     * @return array<string>
     */
    protected function getShipmentPermissions(): array
    {
        return [
            'shipments.view',
            'shipments.create',
            'shipments.update',
            'shipments.cancel',
            'shipments.track',
            'shipments.manage',

            // Courier configurations
            'courier-configs.view',
            'courier-configs.manage',

            // COD remittances
            'remittances.view',
            'remittances.record',
            'remittances.verify',
            'remittances.manage',
        ];
    }

    /**
     * Payment domain permissions.
     *
     * @return array<string>
     */
    protected function getPaymentPermissions(): array
    {
        return [
            'payments.view',
            'payments.record',
            'payments.verify',
            'payments.refund',
            'payments.manage',

            // Bank configurations
            'bank-configs.view',
            'bank-configs.manage',

            // Deposit verification
            'pending-deposits.view',
            'pending-deposits.verify',
            'pending-deposits.reject',
            'pending-deposits.manage',
        ];
    }

    /**
     * Commission domain permissions.
     *
     * @return array<string>
     */
    protected function getCommissionPermissions(): array
    {
        return [
            'commissions.view',
            'commissions.view.own',
            'commissions.approve',
            'commissions.pay',
            'commissions.manage',
        ];
    }

    /**
     * Loyalty domain permissions.
     *
     * @return array<string>
     */
    protected function getLoyaltyPermissions(): array
    {
        return [
            'loyalty.view',
            'loyalty.manage',
            'loyalty.tiers.manage',
            'loyalty.points.adjust',
        ];
    }

    /**
     * Settings domain permissions.
     *
     * @return array<string>
     */
    protected function getSettingsPermissions(): array
    {
        return [
            'settings.view',
            'settings.manage',
            'settings.general',
            'settings.notification',
            'settings.integration',
        ];
    }

    /**
     * System/utility permissions.
     *
     * @return array<string>
     */
    protected function getSystemPermissions(): array
    {
        return [
            'dashboard.view',
            'reports.view',
            'reports.export',
            'activity-logs.view',
            'system.backups',
            'system.maintenance',
        ];
    }

    /**
     * Fulfillment permissions.
     *
     * @return array<string>
     */
    protected function getFulfillmentPermissions(): array
    {
        return [
            'fulfillment.view',
            'fulfillment.pick',
            'fulfillment.pack',
            'fulfillment.export',
            'fulfillment.manage',
        ];
    }

    /**
     * Purchase request permissions.
     *
     * @return array<string>
     */
    protected function getPurchaseRequestPermissions(): array
    {
        return [
            'purchase_request.view',
            'purchase_request.create',
            'purchase_request.manage',
        ];
    }
}
