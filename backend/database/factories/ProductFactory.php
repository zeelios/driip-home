<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Product\Models\Brand;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Product model instances with Vietnamese clothing product data.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            [
                'name'        => 'CK Boxer Brief',
                'description' => 'Quần lót boxer brief chất liệu cotton co giãn cao cấp. Thiết kế ôm vừa vặn, thoáng mát suốt cả ngày. Thích hợp cho cả mặc thường ngày và vận động.',
                'tags'        => ['underwear', 'cotton', 'comfort'],
                'gender'      => 'men',
            ],
            [
                'name'        => 'Nike Air Force 1 Lo',
                'description' => 'Giày sneaker Nike Air Force 1 cổ thấp iconic. Đế Air đệm êm ái, thân giày da cao cấp bền đẹp. Phù hợp với nhiều phong cách thời trang.',
                'tags'        => ['sneaker', 'nike', 'classic'],
                'gender'      => null,
            ],
            [
                'name'        => 'Driip Essential Tee',
                'description' => 'Áo thun basic chất liệu cotton 100% thoáng mát. Logo Driip thêu tinh tế ở ngực trái. Thiết kế tối giản phù hợp mix-match mọi outfit.',
                'tags'        => ['tshirt', 'basic', 'driip'],
                'gender'      => null,
            ],
            [
                'name'        => 'Adidas Trefoil Hoodie',
                'description' => 'Áo hoodie Adidas phong cách thể thao classic. Chất liệu bông xốp giữ ấm tốt, túi kangaroo tiện lợi. Mũ trùm đầu điều chỉnh được.',
                'tags'        => ['hoodie', 'adidas', 'streetwear'],
                'gender'      => null,
            ],
            [
                'name'        => 'New Era 9FIFTY Snapback',
                'description' => 'Mũ snapback New Era 9FIFTY phong cách streetwear. Vành cứng phẳng, khóa sau điều chỉnh vừa mọi đầu. Thêu logo chắc chắn, vải wool cao cấp.',
                'tags'        => ['hat', 'new-era', 'snapback', 'accessory'],
                'gender'      => null,
            ],
            [
                'name'        => 'Driip Cargo Short',
                'description' => 'Quần short cargo nhiều túi tiện dụng. Chất liệu ripstop bền chắc, co giãn 4 chiều thoải mái. Phong cách tactical streetwear năng động.',
                'tags'        => ['shorts', 'cargo', 'driip'],
                'gender'      => 'men',
            ],
            [
                'name'        => 'Converse Chuck 70 Hi',
                'description' => 'Giày Converse Chuck 70 cổ cao huyền thoại. Thân canvas dày dặn, đế chunky đặc trưng. Phong cách retro vintage không bao giờ lỗi mốt.',
                'tags'        => ['sneaker', 'converse', 'high-top', 'classic'],
                'gender'      => null,
            ],
            [
                'name'        => 'Driip OG Fleece Jogger',
                'description' => 'Quần jogger chất liệu fleece mềm mại ấm áp. Lưng thun dây rút điều chỉnh, gấu ống thun co giãn. Hai túi bên và một túi sau tiện lợi.',
                'tags'        => ['jogger', 'fleece', 'driip', 'comfort'],
                'gender'      => null,
            ],
        ];

        $product   = $this->faker->randomElement($products);
        $name      = $product['name'];
        $baseSku   = strtoupper(Str::slug($name, ''));

        return [
            'brand_id'          => Brand::factory(),
            'category_id'       => Category::factory(),
            'name'              => $name,
            'slug'              => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
            'description'       => $product['description'],
            'short_description' => mb_substr($product['description'], 0, 80) . '...',
            'sku_base'          => substr($baseSku, 0, 10),
            'gender'            => $product['gender'],
            'season'            => $this->faker->optional(0.5)->randomElement(['SS25', 'FW24', 'SS24', 'FW23']),
            'tags'              => $product['tags'],
            'status'            => 'active',
            'is_featured'       => $this->faker->boolean(20),
            'published_at'      => $this->faker->dateTimeBetween('-1 year', 'now'),
            'meta_title'        => null,
            'meta_description'  => null,
        ];
    }

    /**
     * State for a featured product.
     *
     * @return static
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * State for a draft (inactive) product.
     *
     * @return static
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'draft',
            'published_at' => null,
        ]);
    }
}
