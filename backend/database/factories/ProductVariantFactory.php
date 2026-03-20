<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating ProductVariant model instances with realistic pricing and attributes.
 *
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Đen', 'Trắng', 'Xám', 'Xanh navy', 'Be'];

        $size  = $this->faker->randomElement($sizes);
        $color = $this->faker->randomElement($colors);

        $comparePrice  = $this->faker->numberBetween(200000, 2000000);
        $sellingPrice  = (int) round($comparePrice * $this->faker->randomFloat(2, 0.6, 0.9));
        $costPrice     = (int) round($sellingPrice * $this->faker->randomFloat(2, 0.55, 0.70));

        return [
            'product_id'       => Product::factory(),
            'sku'              => 'DRP-SKU-' . strtoupper(Str::random(6)),
            'barcode'          => null,
            'attribute_values' => ['Size' => $size, 'Color' => $color],
            'compare_price'    => $comparePrice,
            'cost_price'       => $costPrice,
            'selling_price'    => $sellingPrice,
            'sale_price'       => null,
            'sale_event_id'    => null,
            'weight_grams'     => $this->faker->numberBetween(100, 800),
            'status'           => 'active',
            'sort_order'       => 0,
        ];
    }

    /**
     * State for a specific size/color combination.
     *
     * @param  string  $size
     * @param  string  $color
     * @return static
     */
    public function withAttributes(string $size, string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'attribute_values' => ['Size' => $size, 'Color' => $color],
        ]);
    }

    /**
     * State for an inactive variant.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
