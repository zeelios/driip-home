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
        $this->call([
                // 1. Spatie roles
            RoleSeeder::class,

                // 2-3. Permissions and role-permission mapping
            PermissionSeeder::class,
            RolePermissionSeeder::class,

                // 4. Loyalty tiers
            LoyaltyTierSeeder::class,

                // 5. Settings
            SettingsSeeder::class,

                // 6. Tax configs (commented out for now)
                // TaxConfigSeeder::class,

                // 7. Brands
            BrandSeeder::class,

                // 8. Categories
            CategorySeeder::class,

                // 8.5. Size options linked to categories
            SizeSeeder::class,

                // 9. Staff accounts
            StaffSeeder::class,

                // 10. Courier configs
            CourierConfigSeeder::class,

                // 11. Warehouses
            WarehouseSeeder::class,

                // 12. Suppliers
            SupplierSeeder::class,

                // 13. Products with inventory
            ProductSeeder::class,

                // 14. Customers with loyalty accounts and addresses
            CustomerSeeder::class,

                // 15. Coupons
            CouponSeeder::class,

                // 16. Sale events
            SaleEventSeeder::class,

                // 17. Notification templates
            NotificationTemplateSeeder::class,

                // 18. Sample orders
            OrderSeeder::class,

                // 19. User accounts
            UserSeeder::class,
        ]);
    }
}
