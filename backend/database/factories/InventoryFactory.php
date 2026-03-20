<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Inventory model instances.
 *
 * Note: The Inventory model has no created_at timestamp; only updated_at is tracked.
 *
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityOnHand   = $this->faker->numberBetween(0, 200);
        $quantityReserved = $this->faker->numberBetween(0, min(10, $quantityOnHand));
        $quantityAvailable = max(0, $quantityOnHand - $quantityReserved);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'warehouse_id'       => Warehouse::factory(),
            'quantity_on_hand'   => $quantityOnHand,
            'quantity_reserved'  => $quantityReserved,
            'quantity_available' => $quantityAvailable,
            'quantity_incoming'  => $this->faker->randomElement([0, 0, 0, 10, 20, 50]),
            'reorder_point'      => $this->faker->randomElement([5, 10, 15, 20]),
            'reorder_quantity'   => $this->faker->randomElement([50, 100, 150]),
            'last_counted_at'    => $this->faker->optional(0.5)->dateTimeBetween('-3 months', 'now'),
            'updated_at'         => now(),
        ];
    }

    /**
     * State for out-of-stock inventory.
     *
     * @return static
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand'   => 0,
            'quantity_reserved'  => 0,
            'quantity_available' => 0,
            'quantity_incoming'  => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * State for low-stock inventory (below reorder point).
     *
     * @return static
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand'   => $this->faker->numberBetween(1, 5),
            'quantity_reserved'  => 0,
            'quantity_available' => $this->faker->numberBetween(1, 5),
            'reorder_point'      => 10,
        ]);
    }
}
