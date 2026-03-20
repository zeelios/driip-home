<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating OrderItem model instances.
 *
 * Snapshots product variant data at the time of order creation.
 *
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Đen', 'Trắng', 'Xám', 'Xanh navy', 'Be'];

        $productNames = [
            'Driip Essential Tee',
            'Nike Air Force 1 Lo',
            'Adidas Trefoil Hoodie',
            'New Era 9FIFTY Snapback',
            'CK Boxer Brief',
            'Driip Cargo Short',
            'Converse Chuck 70 Hi',
        ];

        $unitPrice     = $this->faker->numberBetween(100000, 1500000);
        $costPrice     = (int) round($unitPrice * $this->faker->randomFloat(2, 0.55, 0.70));
        $quantity      = $this->faker->numberBetween(1, 3);
        $totalPrice    = $unitPrice * $quantity;

        return [
            'order_id'           => Order::factory(),
            'product_variant_id' => null,
            'sku'                => 'DRP-SKU-' . strtoupper(Str::random(6)),
            'name'               => $this->faker->randomElement($productNames),
            'size'               => $this->faker->randomElement($sizes),
            'color'              => $this->faker->randomElement($colors),
            'unit_price'         => $unitPrice,
            'cost_price'         => $costPrice,
            'quantity'           => $quantity,
            'quantity_returned'  => 0,
            'discount_amount'    => 0,
            'total_price'        => $totalPrice,
        ];
    }
}
