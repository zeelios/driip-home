<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Category model instances with Vietnamese clothing categories.
 *
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'Áo thun', 'description' => 'Áo thun nam nữ các loại, nhiều màu sắc và kiểu dáng.'],
            ['name' => 'Quần short', 'description' => 'Quần short thể thao và thời trang.'],
            ['name' => 'Áo hoodie', 'description' => 'Áo hoodie và sweatshirt phong cách streetwear.'],
            ['name' => 'Phụ kiện', 'description' => 'Phụ kiện thời trang: mũ, túi, dây chuyền, vòng tay.'],
            ['name' => 'Giày dép', 'description' => 'Giày sneaker, dép và các loại giày thời trang.'],
            ['name' => 'Áo sơ mi', 'description' => 'Áo sơ mi nam nữ phong cách.'],
            ['name' => 'Quần dài', 'description' => 'Quần dài jeans, kaki và các loại quần khác.'],
            ['name' => 'Áo khoác', 'description' => 'Áo khoác gió, bomber jacket và các loại áo khoác.'],
            ['name' => 'Đồ lót', 'description' => 'Đồ lót và underwear chất lượng cao.'],
            ['name' => 'Tất vớ', 'description' => 'Tất vớ thể thao và thời trang.'],
        ];

        $category = $this->faker->unique()->randomElement($categories);

        return [
            'parent_id'   => null,
            'name'        => $category['name'],
            'slug'        => Str::slug($category['name']),
            'description' => $category['description'],
            'image'       => null,
            'sort_order'  => $this->faker->numberBetween(0, 10),
            'is_active'   => true,
        ];
    }

    /**
     * State for a subcategory (with a parent).
     *
     * @return static
     */
    public function subcategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory(),
        ]);
    }

    /**
     * State for an inactive category.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
