<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seed 5 top-level categories with 2-3 subcategories each.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
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
}
