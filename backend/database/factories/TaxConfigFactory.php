<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tax\Models\TaxConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating TaxConfig model instances.
 *
 * Note: TaxConfig only has created_at (no updated_at).
 *
 * @extends Factory<TaxConfig>
 */
class TaxConfigFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = TaxConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rate = $this->faker->randomElement(['10.00', '8.00', '5.00', '0.00']);

        return [
            'name'            => "VAT {$rate}%",
            'rate'            => $rate,
            'applies_to'      => 'all',
            'applies_to_ids'  => [],
            'effective_from'  => '2024-01-01',
            'effective_to'    => null,
            'is_active'       => true,
            'created_at'      => now(),
        ];
    }

    /**
     * State for the standard 10% VAT rate.
     *
     * @return static
     */
    public function vat10(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'           => 'Thuế GTGT 10%',
            'rate'           => '10.00',
            'effective_from' => '2024-01-01',
            'effective_to'   => null,
            'is_active'      => true,
        ]);
    }

    /**
     * State for the reduced 8% VAT rate (historical).
     *
     * @return static
     */
    public function vat8(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'           => 'Thuế GTGT 8% (Giảm thuế)',
            'rate'           => '8.00',
            'effective_from' => '2023-01-01',
            'effective_to'   => '2023-12-31',
            'is_active'      => false,
        ]);
    }
}
