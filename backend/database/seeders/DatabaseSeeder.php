<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root database seeder that orchestrates all domain-specific seeders.
 *
 * Seeding order respects FK constraints:
 *  1. Spatie roles
 *  2. Spatie permissions
 *  3. Role-permission mapping
 *  4. Loyalty tiers
 *  5. Settings
 *  6. Tax configs
 *  7. Brands
 *  8. Categories (top-level + subcategories)
 *  9. Staff accounts
 * 10. Courier configs
 * 11. Warehouses
 * 12. Suppliers
 * 13. Products + inventory
 * 14. Customers + loyalty accounts + addresses
 * 15. Coupons
 * 16. Sale events
 * 17. Notification templates
 * 18. Sample orders with items
 * 19. User accounts
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Spatie roles
        $this->call(RoleSeeder::class);

        // 2-3. Permissions and role-permission mapping
        $this->call(PermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);

        // 4. Loyalty tiers
        $this->call(LoyaltyTierSeeder::class);

        // 5. Settings
        $this->call(SettingsSeeder::class);

        // 6. Tax configs (commented out for now)
        // $this->call(TaxConfigSeeder::class);

        // 7. Brands
        $this->call(BrandSeeder::class);

        // 8. Categories
        $this->call(CategorySeeder::class);

        // 8.5. Size options linked to categories
        $this->call(SizeSeeder::class);

        // 9. Staff accounts
        $this->call(StaffSeeder::class);

        // 10. Courier configs
        $this->call(CourierConfigSeeder::class);

        // 11. Warehouses
        $this->call(WarehouseSeeder::class);

        // 12. Suppliers
        $this->call(SupplierSeeder::class);

        // 13. Products with inventory
        $this->call(ProductSeeder::class);

        // 14. Customers with loyalty accounts and addresses
        $this->call(CustomerSeeder::class);

        // 15. Coupons
        $this->call(CouponSeeder::class);

        // 16. Sale events
        $this->call(SaleEventSeeder::class);

        // 17. Notification templates
        $this->call(NotificationTemplateSeeder::class);

        // 18. Sample orders
        $this->call(OrderSeeder::class);

        // 19. User accounts
        $this->call(UserSeeder::class);
    }
}
