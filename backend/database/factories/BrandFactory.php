<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Product\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Brand model instances.
 *
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = [
            ['name' => 'Driip', 'country' => 'VN', 'description' => 'Thương hiệu thời trang streetwear Việt Nam.'],
            ['name' => 'Nike', 'country' => 'US', 'description' => 'Thương hiệu thể thao hàng đầu thế giới.'],
            ['name' => 'Adidas', 'country' => 'DE', 'description' => 'Thương hiệu thể thao đến từ Đức.'],
            ['name' => 'New Era', 'country' => 'US', 'description' => 'Thương hiệu mũ và phụ kiện nổi tiếng thế giới.'],
            ['name' => 'Converse', 'country' => 'US', 'description' => 'Thương hiệu giày sneaker classic huyền thoại.'],
            ['name' => 'Routine', 'country' => 'VN', 'description' => 'Thương hiệu thời trang local Việt Nam.'],
            ['name' => 'Vans', 'country' => 'US', 'description' => 'Thương hiệu giày trượt ván và streetwear.'],
            ['name' => 'Champion', 'country' => 'US', 'description' => 'Thương hiệu thể thao phong cách Mỹ.'],
        ];

        $brand = $this->faker->unique()->randomElement($brands);

        return [
            'name'        => $brand['name'],
            'slug'        => Str::slug($brand['name']) . '-' . $this->faker->unique()->numerify('###'),
            'logo'        => null,
            'description' => $brand['description'],
            'is_active'   => true,
            'sort_order'  => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * State for an inactive brand.
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
