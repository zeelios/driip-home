<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\SizeOption;
use Illuminate\Database\Seeder;

/**
 * Seed size options and assign them to product categories.
 *
 * Creates standard letter sizes (S-XXL) for clothing categories
 * and numeric sizes (38-45) for footwear. Categories are created
 * if they don't already exist via updateOrCreate.
 */
class SizeSeeder extends Seeder
{
    /** @var array<string, array<int, array<string, mixed>>> Category slug to size definitions mapping */
    private const CATEGORY_SIZES = [
        'ao-thun' => [
            ['code' => 'XS', 'display_name' => 'X-Small', 'size_type' => 'letter', 'sort_order' => 1],
            ['code' => 'S', 'display_name' => 'Small', 'size_type' => 'letter', 'sort_order' => 2],
            ['code' => 'M', 'display_name' => 'Medium', 'size_type' => 'letter', 'sort_order' => 3],
            ['code' => 'L', 'display_name' => 'Large', 'size_type' => 'letter', 'sort_order' => 4],
            ['code' => 'XL', 'display_name' => 'X-Large', 'size_type' => 'letter', 'sort_order' => 5],
            ['code' => 'XXL', 'display_name' => 'XX-Large', 'size_type' => 'letter', 'sort_order' => 6],
        ],
        'quan-short' => [
            ['code' => '28', 'display_name' => 'Size 28', 'size_type' => 'numeric', 'sort_order' => 1],
            ['code' => '29', 'display_name' => 'Size 29', 'size_type' => 'numeric', 'sort_order' => 2],
            ['code' => '30', 'display_name' => 'Size 30', 'size_type' => 'numeric', 'sort_order' => 3],
            ['code' => '31', 'display_name' => 'Size 31', 'size_type' => 'numeric', 'sort_order' => 4],
            ['code' => '32', 'display_name' => 'Size 32', 'size_type' => 'numeric', 'sort_order' => 5],
            ['code' => '33', 'display_name' => 'Size 33', 'size_type' => 'numeric', 'sort_order' => 6],
            ['code' => '34', 'display_name' => 'Size 34', 'size_type' => 'numeric', 'sort_order' => 7],
            ['code' => '36', 'display_name' => 'Size 36', 'size_type' => 'numeric', 'sort_order' => 8],
        ],
        'hoodie' => [
            ['code' => 'XS', 'display_name' => 'X-Small', 'size_type' => 'letter', 'sort_order' => 1],
            ['code' => 'S', 'display_name' => 'Small', 'size_type' => 'letter', 'sort_order' => 2],
            ['code' => 'M', 'display_name' => 'Medium', 'size_type' => 'letter', 'sort_order' => 3],
            ['code' => 'L', 'display_name' => 'Large', 'size_type' => 'letter', 'sort_order' => 4],
            ['code' => 'XL', 'display_name' => 'X-Large', 'size_type' => 'letter', 'sort_order' => 5],
            ['code' => 'XXL', 'display_name' => 'XX-Large', 'size_type' => 'letter', 'sort_order' => 6],
        ],
        'giay' => [
            ['code' => '38', 'display_name' => 'EU 38', 'size_type' => 'eu', 'sort_order' => 1],
            ['code' => '39', 'display_name' => 'EU 39', 'size_type' => 'eu', 'sort_order' => 2],
            ['code' => '40', 'display_name' => 'EU 40', 'size_type' => 'eu', 'sort_order' => 3],
            ['code' => '41', 'display_name' => 'EU 41', 'size_type' => 'eu', 'sort_order' => 4],
            ['code' => '42', 'display_name' => 'EU 42', 'size_type' => 'eu', 'sort_order' => 5],
            ['code' => '43', 'display_name' => 'EU 43', 'size_type' => 'eu', 'sort_order' => 6],
            ['code' => '44', 'display_name' => 'EU 44', 'size_type' => 'eu', 'sort_order' => 7],
            ['code' => '45', 'display_name' => 'EU 45', 'size_type' => 'eu', 'sort_order' => 8],
        ],
        'phu-kien' => [
            ['code' => 'OS', 'display_name' => 'One Size', 'size_type' => 'letter', 'sort_order' => 1],
        ],
    ];

    /** @var array<string, array<string, mixed>> Category definitions for creation if missing */
    private const CATEGORY_DEFINITIONS = [
        'ao-thun' => [
            'name' => 'Áo thun',
            'description' => 'Áo thun nam nữ các loại.',
            'sort_order' => 1,
        ],
        'quan-short' => [
            'name' => 'Quần short',
            'description' => 'Quần short thể thao và thời trang.',
            'sort_order' => 2,
        ],
        'hoodie' => [
            'name' => 'Hoodie',
            'description' => 'Áo hoodie và sweatshirt phong cách streetwear.',
            'sort_order' => 3,
        ],
        'phu-kien' => [
            'name' => 'Phụ kiện',
            'description' => 'Mũ, túi, dây chuyền và phụ kiện thời trang.',
            'sort_order' => 4,
        ],
        'giay' => [
            'name' => 'Giày',
            'description' => 'Giày sneaker, dép và các loại giày thời trang.',
            'sort_order' => 5,
        ],
    ];

    /**
     * Run the size seeder.
     *
     * Creates size options and assigns them to categories.
     * Categories are created if they don't already exist.
     *
     * @return void
     */
    public function run(): void
    {
        foreach (self::CATEGORY_SIZES as $categorySlug => $sizeDefinitions) {
            // Get or create the category
            $category = $this->getOrCreateCategory($categorySlug);

            if ($category === null) {
                continue;
            }

            // Create sizes and attach to category
            $this->createAndAttachSizes($category, $sizeDefinitions);
        }
    }

    /**
     * Get an existing category or create it if it doesn't exist.
     *
     * @param string $slug The category slug identifier
     * @return Category|null The category model or null on failure
     */
    private function getOrCreateCategory(string $slug): ?Category
    {
        $definition = self::CATEGORY_DEFINITIONS[$slug] ?? null;

        if ($definition === null) {
            return null;
        }

        return Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'parent_id' => null,
                'sort_order' => $definition['sort_order'],
                'is_active' => true,
            ]
        );
    }

    /**
     * Create size options and attach them to a category.
     *
     * @param Category $category The category to attach sizes to
     * @param array<int, array<string, mixed>> $sizeDefinitions Array of size definitions
     * @return void
     */
    private function createAndAttachSizes(Category $category, array $sizeDefinitions): void
    {
        $sizeIds = [];

        foreach ($sizeDefinitions as $definition) {
            $sizeOption = SizeOption::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'display_name' => $definition['display_name'],
                    'size_type' => $definition['size_type'],
                    'sort_order' => $definition['sort_order'],
                ]
            );

            if ($sizeOption !== null) {
                $sizeIds[$sizeOption->id] = ['sort_order' => $definition['sort_order']];
            }
        }

        // Sync with pivot data (sort_order)
        if (!empty($sizeIds)) {
            $category->sizeOptions()->syncWithoutDetaching($sizeIds);
        }
    }
}
