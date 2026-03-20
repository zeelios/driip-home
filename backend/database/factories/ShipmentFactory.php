<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Order\Models\Order;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Shipment model instances.
 *
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weightKg = number_format($this->faker->randomFloat(2, 0.2, 2.0), 2);
        $codAmount = $this->faker->numberBetween(100000, 5000000);

        return [
            'order_id'              => Order::factory(),
            'courier_code'          => $this->faker->randomElement(['ghn', 'ghtk']),
            'tracking_number'       => null,
            'internal_reference'    => null,
            'status'                => 'draft',
            'label_url'             => null,
            'cod_amount'            => $codAmount,
            'cod_collected'         => false,
            'shipping_fee_quoted'   => $this->faker->randomElement([25000, 30000, 35000]),
            'shipping_fee_actual'   => null,
            'weight_kg'             => $weightKg,
            'estimated_delivery_at' => null,
            'delivered_at'          => null,
            'failed_attempts'       => 0,
            'courier_request'       => null,
            'courier_response'      => null,
            'created_by'            => User::factory(),
        ];
    }

    /**
     * State for a shipped (in-transit) shipment.
     *
     * @return static
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'                => 'shipped',
            'tracking_number'       => strtoupper($this->faker->bothify('GHN#########')),
            'estimated_delivery_at' => now()->addDays(3)->format('Y-m-d'),
        ]);
    }

    /**
     * State for a delivered shipment.
     *
     * @return static
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'delivered',
            'delivered_at' => now()->subDay(),
            'cod_collected' => true,
        ]);
    }
}
