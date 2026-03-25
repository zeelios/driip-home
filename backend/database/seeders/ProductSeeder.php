<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Product\Models\Brand;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed 5 products with inventory in each warehouse.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
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

        foreach ($productDefs as $i => $def) {
            $brandId = $brands->get($def['brand_slug'])?->id;
            $categoryId = $categories->get($def['category_slug'])?->id;

            $slug = Str::slug($def['name']);
            $skuBase = strtoupper(substr(Str::slug($def['name'], ''), 0, 8));

            $comparePrice = $def['price_range'][1];
            $sellingPrice = $def['price_range'][0];
            $costPrice = (int) round($sellingPrice * 0.60);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'name' => $def['name'],
                    'slug' => $slug,
                    'description' => $def['description'],
                    'short_description' => substr($def['description'], 0, 80) . '...',
                    'sku' => 'DRP-' . $skuBase,
                    'gender' => null,
                    'season' => 'SS25',
                    'tags' => $def['tags'],
                    'compare_price' => $comparePrice,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'sale_price' => null,
                    'weight_grams' => 250,
                    'status' => 'active',
                    'is_featured' => $i < 2,
                    'published_at' => now(),
                ],
            );

            // Create inventory for each warehouse
            foreach ($warehouses as $warehouse) {
                $onHand = rand(10, 100);
                $reserved = rand(0, min(5, $onHand));
                $available = $onHand - $reserved;

                Inventory::updateOrCreate(
                    [
                        'product_id' => $product->id,
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
