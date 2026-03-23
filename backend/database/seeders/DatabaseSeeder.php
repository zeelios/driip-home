<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerAddress;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\Supplier;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Product\Models\Brand;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Shipment\Models\CourierConfig;
use App\Domain\Warehouse\Models\Warehouse;
use App\Domain\Coupon\Models\Coupon;
use App\Domain\SaleEvent\Models\SaleEvent;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Root database seeder that orchestrates all domain-specific seeders.
 *
 * Seeding order respects FK constraints:
 *  1. Spatie roles
 *  2. Loyalty tiers
 *  3. Settings
 *  4. Tax configs
 *  5. Brands
 *  6. Categories (top-level + subcategories)
 *  7. Staff accounts
 *  8. Courier configs
 *  9. Warehouses
 * 10. Suppliers
 * 11. Products + variants + inventory
 * 12. Customers + loyalty accounts + addresses
 * 13. Coupons
 * 14. Sale events
 * 15. Notification templates
 * 16. Sample orders with items
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // 1. Spatie roles
        $this->call(RoleSeeder::class);

        // 2. Loyalty tiers
        $this->call(LoyaltyTierSeeder::class);

        // 3. Settings
        $this->call(SettingsSeeder::class);

        // 4. Tax configs
        // $this->call(TaxConfigSeeder::class);

        // 5. Brands — 5 specific brands, Driip as house brand
        $this->seedBrands();

        // 6. Categories — 5 top-level with 2-3 subcategories each
        $this->seedCategories();

        // 7. Staff accounts
        $this->call(StaffSeeder::class);

        // 8. Courier configs (GHN and GHTK)
        $this->seedCourierConfigs();

        // 9. Warehouses
        $this->seedWarehouses();

        // 10. Suppliers
        $this->seedSuppliers();

        // 11. Products with variants and inventory
        $this->seedProducts();

        // 12. Customers with loyalty accounts and addresses
        $this->seedCustomers();

        // 13. Coupons
        $this->seedCoupons();

        // 14. Sale events
        $this->seedSaleEvents();

        // 15. Notification templates
        $this->call(NotificationTemplateSeeder::class);

        // 16. Sample orders
        $this->seedOrders();

        // 16. User accounts
        $this->seedUsers();
    }

    /**
     * Seed 5 brands: Driip (house brand), Nike, Adidas, New Era, Converse.
     *
     * @return void
     */
    private function seedBrands(): void
    {
        $brands = [
            [
                'name' => 'Driip',
                'slug' => 'driip',
                'description' => 'Thương hiệu thời trang streetwear Việt Nam — Driip.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Thương hiệu thể thao hàng đầu thế giới.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Thương hiệu thể thao đến từ Đức.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'New Era',
                'slug' => 'new-era',
                'description' => 'Thương hiệu mũ và phụ kiện nổi tiếng thế giới.',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Converse',
                'slug' => 'converse',
                'description' => 'Thương hiệu giày sneaker classic huyền thoại.',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(['slug' => $brand['slug']], $brand);
        }
    }

    /**
     * Seed 5 top-level categories with 2-3 subcategories each.
     *
     * @return void
     */
    private function seedCategories(): void
    {
        $tree = [
            [
                'name' => 'Áo thun',
                'slug' => 'ao-thun',
                'description' => 'Áo thun nam nữ các loại.',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Áo thun basic', 'slug' => 'ao-thun-basic'],
                    ['name' => 'Áo thun graphic', 'slug' => 'ao-thun-graphic'],
                    ['name' => 'Áo thun polo', 'slug' => 'ao-thun-polo'],
                ],
            ],
            [
                'name' => 'Quần short',
                'slug' => 'quan-short',
                'description' => 'Quần short thể thao và thời trang.',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Quần short thể thao', 'slug' => 'quan-short-the-thao'],
                    ['name' => 'Quần short cargo', 'slug' => 'quan-short-cargo'],
                ],
            ],
            [
                'name' => 'Hoodie',
                'slug' => 'hoodie',
                'description' => 'Áo hoodie và sweatshirt phong cách streetwear.',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Hoodie pullover', 'slug' => 'hoodie-pullover'],
                    ['name' => 'Hoodie zip-up', 'slug' => 'hoodie-zip-up'],
                    ['name' => 'Crewneck sweater', 'slug' => 'crewneck-sweater'],
                ],
            ],
            [
                'name' => 'Phụ kiện',
                'slug' => 'phu-kien',
                'description' => 'Mũ, túi, dây chuyền và phụ kiện thời trang.',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Mũ snapback', 'slug' => 'mu-snapback'],
                    ['name' => 'Tất vớ', 'slug' => 'tat-voc'],
                    ['name' => 'Túi đeo vai', 'slug' => 'tui-deo-vai'],
                ],
            ],
            [
                'name' => 'Giày',
                'slug' => 'giay',
                'description' => 'Giày sneaker, dép và các loại giày thời trang.',
                'sort_order' => 5,
                'children' => [
                    ['name' => 'Sneaker cổ thấp', 'slug' => 'sneaker-co-thap'],
                    ['name' => 'Sneaker cổ cao', 'slug' => 'sneaker-co-cao'],
                ],
            ],
        ];

        foreach ($tree as $parentData) {
            $children = $parentData['children'] ?? [];
            unset($parentData['children']);

            $parent = Category::updateOrCreate(
                ['slug' => $parentData['slug']],
                array_merge($parentData, ['parent_id' => null, 'is_active' => true]),
            );

            foreach ($children as $childData) {
                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    array_merge($childData, [
                        'parent_id' => $parent->id,
                        'description' => null,
                        'sort_order' => 0,
                        'is_active' => true,
                    ]),
                );
            }
        }
    }

    /**
     * Seed GHN and GHTK courier configurations with placeholder credentials.
     *
     * @return void
     */
    private function seedCourierConfigs(): void
    {
        $configs = [
            [
                'courier_code' => 'ghn',
                'name' => 'Giao Hàng Nhanh',
                'api_endpoint' => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
                'api_key' => 'placeholder_ghn_api_key',
                'api_secret' => null,
                'account_id' => '12345',
                'pickup_hub_code' => 'SGBQ',
                'pickup_address' => [
                    'name' => 'Kho Driip HCM',
                    'phone' => '0901234567',
                    'address' => '123 Nguyễn Huệ',
                    'ward' => 'Phường Bến Nghé',
                    'district' => 'Quận 1',
                    'province' => 'TP. Hồ Chí Minh',
                ],
                'webhook_secret' => 'placeholder_ghn_webhook_secret',
                'is_active' => true,
                'settings' => ['service_type_id' => 2],
            ],
            [
                'courier_code' => 'ghtk',
                'name' => 'Giao Hàng Tiết Kiệm',
                'api_endpoint' => 'https://services.giaohangtietkiem.vn',
                'api_key' => 'placeholder_ghtk_api_key',
                'api_secret' => null,
                'account_id' => '67890',
                'pickup_hub_code' => null,
                'pickup_address' => [
                    'name' => 'Kho Driip HCM',
                    'phone' => '0901234567',
                    'address' => '123 Nguyễn Huệ, Phường Bến Nghé, Quận 1',
                    'province' => 'TP. Hồ Chí Minh',
                ],
                'webhook_secret' => 'placeholder_ghtk_webhook_secret',
                'is_active' => true,
                'settings' => [],
            ],
        ];

        foreach ($configs as $config) {
            CourierConfig::updateOrCreate(
                ['courier_code' => $config['courier_code']],
                $config,
            );
        }
    }

    /**
     * Seed 2 warehouses: Kho Hà Nội and Kho TP.HCM.
     *
     * @return void
     */
    private function seedWarehouses(): void
    {
        $warehouses = [
            [
                'code' => 'WH-HN-001',
                'name' => 'Kho Hà Nội',
                'type' => 'main',
                'address' => '123 Nguyễn Văn Cừ, Quận Long Biên',
                'province' => 'Hà Nội',
                'district' => 'Quận Long Biên',
                'phone' => '02412345678',
                'is_active' => true,
            ],
            [
                'code' => 'WH-HCM-001',
                'name' => 'Kho TP.HCM',
                'type' => 'main',
                'address' => '456 Kinh Dương Vương, Quận Bình Tân',
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận Bình Tân',
                'phone' => '02812345678',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                $warehouse,
            );
        }
    }

    /**
     * Seed 3 suppliers using the SupplierFactory.
     *
     * @return void
     */
    private function seedSuppliers(): void
    {
        $supplierData = [
            [
                'code' => 'DRP-SUP-001',
                'name' => 'Công ty TNHH Dệt May Thành Công',
                'contact_name' => 'Nguyễn Văn Thành',
                'email' => 'thanh@detmaythanhcong.vn',
                'phone' => '0901111111',
                'address' => '123 Trường Chinh, Quận Tân Bình, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'NET30',
                'is_active' => true,
            ],
            [
                'code' => 'DRP-SUP-002',
                'name' => 'Xưởng May Thời Trang Minh Châu',
                'contact_name' => 'Trần Thị Châu',
                'email' => 'chau@minhchau.vn',
                'phone' => '0902222222',
                'address' => '456 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'NET15',
                'is_active' => true,
            ],
            [
                'code' => 'DRP-SUP-003',
                'name' => 'Công ty CP Sản Xuất Phụ Kiện Bình Minh',
                'contact_name' => 'Lê Quốc Huy',
                'email' => 'huy@binhminh.vn',
                'phone' => '0903333333',
                'address' => '789 Hòa Bình, Quận Tân Phú, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'COD',
                'is_active' => true,
            ],
        ];

        foreach ($supplierData as $data) {
            Supplier::updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }
    }

    /**
     * Seed 5 products with 4 variants each, plus inventory in each warehouse.
     *
     * @return void
     */
    private function seedProducts(): void
    {
        $brands = Brand::all()->keyBy('slug');
        $categories = Category::all()->keyBy('slug');
        $warehouses = Warehouse::all();

        $productDefs = [
            [
                'brand_slug' => 'driip',
                'category_slug' => 'ao-thun-basic',
                'name' => 'Driip Essential Tee',
                'description' => 'Ao thun basic chat lieu cotton 100% thoang mat. Logo Driip theu tinh te o nguc trai. Thiet ke toi gian phu hop mix-match moi outfit.',
                'tags' => ['tshirt', 'basic', 'driip', 'cotton'],
                'price_range' => [250000, 350000],
            ],
            [
                'brand_slug' => 'nike',
                'category_slug' => 'sneaker-co-thap',
                'name' => 'Nike Air Force 1 Lo',
                'description' => 'Giay sneaker Nike Air Force 1 co thap iconic. De Air dem em ai, than giay da cao cap ben dep. Phu hop voi nhieu phong cach thoi trang.',
                'tags' => ['sneaker', 'nike', 'classic', 'leather'],
                'price_range' => [1800000, 2200000],
            ],
            [
                'brand_slug' => 'adidas',
                'category_slug' => 'hoodie-pullover',
                'name' => 'Adidas Trefoil Hoodie',
                'description' => 'Ao hoodie Adidas phong cach the thao classic. Chat lieu bong xop giu am tot, tui kangaroo tien loi. Mu trum dau dieu chinh duoc.',
                'tags' => ['hoodie', 'adidas', 'streetwear', 'cotton'],
                'price_range' => [800000, 1200000],
            ],
            [
                'brand_slug' => 'new-era',
                'category_slug' => 'mu-snapback',
                'name' => 'New Era 9FIFTY Snapback',
                'description' => 'Mu snapback New Era 9FIFTY phong cach streetwear. Vanh cung phang, khoa sau dieu chinh vua moi dau. Theu logo chac chan, vai wool cao cap.',
                'tags' => ['hat', 'new-era', 'snapback', 'accessory'],
                'price_range' => [500000, 800000],
            ],
            [
                'brand_slug' => 'driip',
                'category_slug' => 'quan-short-cargo',
                'name' => 'Driip Cargo Short',
                'description' => 'Quan short cargo nhieu tui tien dung. Chat lieu ripstop ben chac, co gian 4 chieu thoai mai. Phong cach tactical streetwear nang dong.',
                'tags' => ['shorts', 'cargo', 'driip', 'tactical'],
                'price_range' => [400000, 600000],
            ],
        ];

        $variantCombinations = [
            ['Size' => 'M', 'Color' => 'Đen'],
            ['Size' => 'M', 'Color' => 'Trắng'],
            ['Size' => 'L', 'Color' => 'Đen'],
            ['Size' => 'XL', 'Color' => 'Xám'],
        ];

        foreach ($productDefs as $i => $def) {
            $brandId = $brands->get($def['brand_slug'])?->id;
            $categoryId = $categories->get($def['category_slug'])?->id;

            $slug = Str::slug($def['name']);
            $skuBase = strtoupper(substr(Str::slug($def['name'], ''), 0, 8));

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'name' => $def['name'],
                    'slug' => $slug,
                    'description' => $def['description'],
                    'short_description' => substr($def['description'], 0, 80) . '...',
                    'sku_base' => $skuBase,
                    'gender' => null,
                    'season' => 'SS25',
                    'tags' => $def['tags'],
                    'status' => 'active',
                    'is_featured' => $i < 2,
                    'published_at' => now(),
                ],
            );

            $comparePrice = $def['price_range'][1];
            $sellingPrice = $def['price_range'][0];
            $costPrice = (int) round($sellingPrice * 0.60);

            foreach ($variantCombinations as $j => $attrs) {
                $sku = 'DRP-' . $skuBase . '-' . $attrs['Size'] . '-' . mb_strtoupper(substr($attrs['Color'], 0, 2));

                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'attribute_values' => $attrs,
                        'compare_price' => $comparePrice,
                        'cost_price' => $costPrice,
                        'selling_price' => $sellingPrice,
                        'sale_price' => null,
                        'weight_grams' => 250,
                        'status' => 'active',
                        'sort_order' => $j,
                    ],
                );

                // Create inventory for each warehouse
                foreach ($warehouses as $warehouse) {
                    $onHand = rand(10, 100);
                    $reserved = rand(0, min(5, $onHand));
                    $available = $onHand - $reserved;

                    Inventory::updateOrCreate(
                        [
                            'product_variant_id' => $variant->id,
                            'warehouse_id' => $warehouse->id,
                        ],
                        [
                            'quantity_on_hand' => $onHand,
                            'quantity_reserved' => $reserved,
                            'quantity_available' => $available,
                            'quantity_incoming' => 0,
                            'reorder_point' => 10,
                            'reorder_quantity' => 50,
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }

    /**
     * Seed 20 customers with loyalty accounts and addresses.
     *
     * @return void
     */
    private function seedCustomers(): void
    {
        $bronzeTier = LoyaltyTier::where('slug', 'bronze')->first();
        $silverTier = LoyaltyTier::where('slug', 'silver')->first();
        $goldTier = LoyaltyTier::where('slug', 'gold')->first();
        $diamondTier = LoyaltyTier::where('slug', 'diamond')->first();

        $customerDefs = [
            ['first_name' => 'Nguyễn', 'last_name' => 'Văn An', 'phone' => '0901100001', 'email' => 'van.an@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Trần', 'last_name' => 'Thị Lan', 'phone' => '0901100002', 'email' => 'thi.lan@example.com', 'tier' => $silverTier, 'spending' => 2500000],
            ['first_name' => 'Lê', 'last_name' => 'Minh Đức', 'phone' => '0901100003', 'email' => 'minh.duc@example.com', 'tier' => $goldTier, 'spending' => 8000000],
            ['first_name' => 'Phạm', 'last_name' => 'Thị Hoa', 'phone' => '0901100004', 'email' => 'thi.hoa@example.com', 'tier' => $bronzeTier, 'spending' => 500000],
            ['first_name' => 'Hoàng', 'last_name' => 'Quốc Huy', 'phone' => '0901100005', 'email' => 'quoc.huy@example.com', 'tier' => $diamondTier, 'spending' => 35000000],
            ['first_name' => 'Huỳnh', 'last_name' => 'Thị Mai', 'phone' => '0901100006', 'email' => 'thi.mai@example.com', 'tier' => $silverTier, 'spending' => 1800000],
            ['first_name' => 'Phan', 'last_name' => 'Văn Bình', 'phone' => '0901100007', 'email' => 'van.binh@example.com', 'tier' => $bronzeTier, 'spending' => 300000],
            ['first_name' => 'Vũ', 'last_name' => 'Ngọc Ánh', 'phone' => '0901100008', 'email' => 'ngoc.anh@example.com', 'tier' => $goldTier, 'spending' => 12000000],
            ['first_name' => 'Đặng', 'last_name' => 'Trung Kiên', 'phone' => '0901100009', 'email' => 'trung.kien@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Bùi', 'last_name' => 'Thị Thu', 'phone' => '0901100010', 'email' => 'thi.thu@example.com', 'tier' => $silverTier, 'spending' => 2000000],
            ['first_name' => 'Đỗ', 'last_name' => 'Anh Tuấn', 'phone' => '0901100011', 'email' => null, 'tier' => $bronzeTier, 'spending' => 150000],
            ['first_name' => 'Hồ', 'last_name' => 'Kim Oanh', 'phone' => '0901100012', 'email' => 'kim.oanh@example.com', 'tier' => $goldTier, 'spending' => 6500000],
            ['first_name' => 'Ngô', 'last_name' => 'Đức Long', 'phone' => '0901100013', 'email' => 'duc.long@example.com', 'tier' => $bronzeTier, 'spending' => 800000],
            ['first_name' => 'Dương', 'last_name' => 'Bích Ngọc', 'phone' => '0901100014', 'email' => 'bich.ngoc@example.com', 'tier' => $silverTier, 'spending' => 3200000],
            ['first_name' => 'Lý', 'last_name' => 'Thanh Tùng', 'phone' => '0901100015', 'email' => 'thanh.tung@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Trịnh', 'last_name' => 'Minh Châu', 'phone' => '0901100016', 'email' => null, 'tier' => $bronzeTier, 'spending' => 600000],
            ['first_name' => 'Đinh', 'last_name' => 'Hữu Nam', 'phone' => '0901100017', 'email' => 'huu.nam@example.com', 'tier' => $goldTier, 'spending' => 9000000],
            ['first_name' => 'Mai', 'last_name' => 'Văn Phong', 'phone' => '0901100018', 'email' => 'van.phong@example.com', 'tier' => $bronzeTier, 'spending' => 1200000],
            ['first_name' => 'Cao', 'last_name' => 'Thị Hương', 'phone' => '0901100019', 'email' => 'thi.huong@example.com', 'tier' => $silverTier, 'spending' => 4500000],
            ['first_name' => 'Tô', 'last_name' => 'Quốc Khánh', 'phone' => '0901100020', 'email' => 'quoc.khanh@example.com', 'tier' => $diamondTier, 'spending' => 25000000],
        ];

        $sources = ['facebook', 'instagram', 'tiktok', 'website', 'referral', 'walk_in'];
        $provinces = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương'];
        $districts = ['Quận 1', 'Quận 3', 'Quận Hoàn Kiếm', 'Quận Đống Đa', 'Quận Hải Châu'];
        $streets = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Điện Biên Phủ', 'Cách Mạng Tháng 8'];

        foreach ($customerDefs as $i => $def) {
            $lifetimeSpending = $def['spending'];
            $lifetimePoints = (int) floor($lifetimeSpending / 1000);
            $totalOrders = $lifetimeSpending > 0 ? rand(1, max(1, (int) ($lifetimeSpending / 500000))) : 0;

            $customer = Customer::updateOrCreate(
                ['phone' => $def['phone']],
                [
                    'customer_code' => sprintf('DRP-C-%05d', $i + 1),
                    'first_name' => $def['first_name'],
                    'last_name' => $def['last_name'],
                    'email' => $def['email'],
                    'phone' => $def['phone'],
                    'gender' => $i % 3 === 0 ? 'male' : ($i % 3 === 1 ? 'female' : null),
                    'source' => $sources[$i % count($sources)],
                    'tags' => $lifetimeSpending > 20000000 ? ['vip'] : ($lifetimeSpending > 5000000 ? ['regular'] : ['new']),
                    'is_blocked' => false,
                    'total_orders' => $totalOrders,
                    'total_spent' => $lifetimeSpending,
                    'last_ordered_at' => $totalOrders > 0 ? now()->subDays(rand(1, 90)) : null,
                    'referral_code' => Str::upper(Str::substr(Str::slug($def['first_name'], ''), 0, 3) . sprintf('%04d', $i + 1)),
                ],
            );

            // Create loyalty account
            $tier = $def['tier'];
            LoyaltyAccount::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'tier_id' => $tier?->id,
                    'points_balance' => $lifetimePoints,
                    'lifetime_points' => $lifetimePoints,
                    'lifetime_spending' => $lifetimeSpending,
                    'tier_achieved_at' => $lifetimePoints > 0 ? now()->subMonths(rand(1, 12)) : null,
                ],
            );

            // Create default address
            $province = $provinces[$i % count($provinces)];
            $district = $districts[$i % count($districts)];
            $street = $streets[$i % count($streets)];

            CustomerAddress::updateOrCreate(
                ['customer_id' => $customer->id, 'is_default' => true],
                [
                    'label' => 'Nhà',
                    'recipient_name' => $def['first_name'] . ' ' . $def['last_name'],
                    'phone' => $def['phone'],
                    'province' => $province,
                    'district' => $district,
                    'ward' => 'Phường ' . rand(1, 20),
                    'address' => rand(1, 300) . ' ' . $street,
                    'is_default' => true,
                ],
            );
        }
    }

    /**
     * Seed 3 coupons: WELCOME10, SALE50K, FREESHIP.
     *
     * @return void
     */
    private function seedCoupons(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Chào mừng khách hàng mới - Giảm 10%',
                'description' => 'Giảm 10% cho đơn hàng đầu tiên của khách hàng mới.',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 200000,
                'max_discount_amount' => 200000,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => null,
                'max_uses_per_customer' => 1,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'SALE50K',
                'name' => 'Giảm 50.000đ cho đơn từ 300.000đ',
                'description' => 'Giảm ngay 50.000 VND cho đơn hàng từ 300.000 VND.',
                'type' => 'fixed_amount',
                'value' => 50000,
                'min_order_amount' => 300000,
                'max_discount_amount' => null,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => 500,
                'max_uses_per_customer' => 2,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Miễn phí vận chuyển',
                'description' => 'Miễn phí vận chuyển cho mọi đơn hàng không giới hạn giá trị.',
                'type' => 'free_shipping',
                'value' => 0,
                'min_order_amount' => null,
                'max_discount_amount' => null,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => 200,
                'max_uses_per_customer' => 1,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon,
            );
        }
    }

    /**
     * Seed 1 scheduled flash sale and 1 draft drop launch.
     *
     * @return void
     */
    private function seedSaleEvents(): void
    {
        $admin = User::where('email', 'admin@driip.com')->first();

        $events = [
            [
                'name' => 'Driip Flash Sale Hè 2025',
                'slug' => 'driip-flash-sale-he-2025',
                'description' => 'Flash sale lớn nhất hè 2025 — giảm giá lên đến 50% toàn bộ sản phẩm.',
                'type' => 'flash_sale',
                'status' => 'scheduled',
                'starts_at' => now()->addDays(7),
                'ends_at' => now()->addDays(8),
                'max_orders_total' => 300,
                'is_public' => true,
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Driip SS25 Drop 01',
                'slug' => 'driip-ss25-drop-01',
                'description' => 'Bộ sưu tập mùa hè 2025 chính thức ra mắt — limited edition.',
                'type' => 'drop_launch',
                'status' => 'draft',
                'starts_at' => now()->addDays(30),
                'ends_at' => now()->addDays(37),
                'max_orders_total' => null,
                'is_public' => false,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($events as $event) {
            SaleEvent::updateOrCreate(
                ['slug' => $event['slug']],
                $event,
            );
        }
    }

    /**
     * Seed 10 sample orders with order items.
     *
     * @return void
     */
    private function seedOrders(): void
    {
        $customers = Customer::limit(10)->get();
        $variants = ProductVariant::limit(10)->get();
        $warehouses = Warehouse::all();

        if ($customers->isEmpty() || $variants->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'confirmed', 'processing', 'packed', 'delivered', 'cancelled'];
        $paymentMethods = ['cod', 'bank_transfer', 'momo', 'vnpay'];

        $shippingData = [
            ['province' => 'TP. Hồ Chí Minh', 'district' => 'Quận 1', 'ward' => 'Phường Bến Nghé', 'address' => '123 Nguyễn Huệ'],
            ['province' => 'Hà Nội', 'district' => 'Quận Hoàn Kiếm', 'ward' => 'Phường Hàng Bài', 'address' => '456 Đinh Tiên Hoàng'],
            ['province' => 'Đà Nẵng', 'district' => 'Quận Hải Châu', 'ward' => 'Phường Thạch Thang', 'address' => '789 Bạch Đằng'],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $customer = $customers->get(($i - 1) % $customers->count());
            $variant = $variants->get(($i - 1) % $variants->count());
            $warehouse = $warehouses->first();
            $shipping = $shippingData[$i % count($shippingData)];
            $status = $statuses[($i - 1) % count($statuses)];
            $payment = $paymentMethods[($i - 1) % count($paymentMethods)];

            $quantity = rand(1, 3);
            $unitPrice = $variant?->selling_price ?? 300000;
            $subtotal = $unitPrice * $quantity;
            $shippingFee = $subtotal >= 500000 ? 0 : 30000;
            $totalBefore = $subtotal + $shippingFee;

            $date = now()->subDays(rand(1, 60));
            $orderNumber = 'DRP-' . $date->format('ymd') . '-' . sprintf('%04d', $i);

            $isPaid = in_array($status, ['delivered', 'packed'], true);

            $order = Order::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'order_number' => $orderNumber,
                    'customer_id' => $customer?->id,
                    'status' => $status,
                    'payment_status' => $isPaid ? 'paid' : 'unpaid',
                    'payment_method' => $payment,
                    'paid_at' => $isPaid ? $date->copy()->addHours(2) : null,
                    'subtotal' => $subtotal,
                    'coupon_discount' => 0,
                    'loyalty_points_used' => 0,
                    'loyalty_discount' => 0,
                    'shipping_fee' => $shippingFee,
                    'vat_rate' => 0,
                    'vat_amount' => 0,
                    'total_before_tax' => $totalBefore,
                    'total_after_tax' => $totalBefore,
                    'cost_total' => (int) round($subtotal * 0.6),
                    'shipping_name' => $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'Khách lẻ',
                    'shipping_phone' => $customer?->phone ?? '0901234567',
                    'shipping_province' => $shipping['province'],
                    'shipping_district' => $shipping['district'],
                    'shipping_ward' => $shipping['ward'],
                    'shipping_address' => $shipping['address'],
                    'warehouse_id' => $warehouse?->id,
                    'source' => 'website',
                    'tags' => [],
                    'confirmed_at' => in_array($status, ['confirmed', 'processing', 'packed', 'delivered'], true) ? $date->copy()->addHour() : null,
                    'delivered_at' => $status === 'delivered' ? $date->copy()->addDays(3) : null,
                    'cancelled_at' => $status === 'cancelled' ? $date->copy()->addHours(2) : null,
                    'cancellation_reason' => $status === 'cancelled' ? 'Khách hàng yêu cầu hủy đơn' : null,
                ],
            );

            // Create order item
            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'sku' => $variant?->sku ?? 'DRP-SKU-DEFAULT'],
                [
                    'order_id' => $order->id,
                    'product_variant_id' => $variant?->id,
                    'sku' => $variant?->sku ?? 'DRP-SKU-DEFAULT',
                    'name' => $variant?->product?->name ?? 'Sản phẩm Driip',
                    'size' => $variant?->attribute_values['Size'] ?? 'M',
                    'color' => $variant?->attribute_values['Color'] ?? 'Đen',
                    'unit_price' => $unitPrice,
                    'cost_price' => $variant?->cost_price ?? (int) round($unitPrice * 0.6),
                    'quantity' => $quantity,
                    'quantity_returned' => 0,
                    'discount_amount' => 0,
                    'total_price' => $unitPrice * $quantity,
                ],
            );
        }
    }

    /**
     * Create default user
     *
     * @@return void
     */
    private function seedUsers(): void
    {
        // Create user
        $user = User::updateOrCreate(
            ['email' => 'admin@driip.io'],
            [
                'name' => 'admin',
                'email' => 'admin@driip.io',
                'password' => Hash::make('password'),
            ],
        );

        // assign the highest role
        $user->syncRoles(['super-admin']);
    }
}
