<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shipment\Models\CourierCODRemittance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating CourierCODRemittance instances.
 *
 * @extends Factory<CourierCODRemittance>
 */
class CourierCODRemittanceFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = CourierCODRemittance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $couriers = ['ghn', 'ghtk', 'vnpost', 'jt'];
        $courier  = $this->faker->randomElement($couriers);

        $total = $this->faker->numberBetween(1000000, 50000000);
        $fees  = (int) ($total * 0.05);

        return [
            'courier_code'         => $courier,
            'remittance_reference' => strtoupper($courier) . '-REM-' . date('Y') . '-' . $this->faker->numerify('###'),
            'period_from'          => now()->subDays(30)->toDateString(),
            'period_to'            => now()->subDays(1)->toDateString(),
            'total_cod_collected'  => $total,
            'total_fees_deducted'  => $fees,
            'net_remittance'       => $total - $fees,
            'status'               => $this->faker->randomElement(['pending', 'received', 'reconciled']),
            'received_at'          => null,
            'notes'                => null,
        ];
    }
}
