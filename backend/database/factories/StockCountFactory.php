<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\StockCount;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating StockCount model instances.
 *
 * @extends Factory<StockCount>
 */
class StockCountFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = StockCount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $types = ['full', 'partial', 'spot'];

        return [
            'count_number'          => sprintf('DRP-SC-%04d', $counter),
            'warehouse_id'          => Warehouse::factory(),
            'type'                  => $this->faker->randomElement($types),
            'status'                => 'draft',
            'scheduled_at'          => $this->faker->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'started_at'            => null,
            'completed_at'          => null,
            'approved_by'           => null,
            'approved_at'           => null,
            'total_variance_qty'    => null,
            'total_variance_value'  => null,
            'notes'                 => $this->faker->optional(0.3)->sentence(),
            'created_by'            => User::factory(),
        ];
    }

    /**
     * State for a completed stock count.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'               => 'completed',
            'started_at'           => now()->subDays(2),
            'completed_at'         => now()->subDay(),
            'total_variance_qty'   => $this->faker->numberBetween(-20, 20),
            'total_variance_value' => $this->faker->numberBetween(-500000, 500000),
        ]);
    }
}
