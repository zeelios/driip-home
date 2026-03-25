<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Product\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Seed 5 brands: Driip (house brand), Nike, Adidas, New Era, Converse.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
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
}
