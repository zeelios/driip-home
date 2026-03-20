<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shipment\Models\CourierConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating CourierConfig model instances.
 *
 * @extends Factory<CourierConfig>
 */
class CourierConfigFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = CourierConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $couriers = [
            [
                'courier_code'    => 'ghn',
                'name'            => 'Giao Hàng Nhanh',
                'api_endpoint'    => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
                'pickup_hub_code' => 'SGBQ',
            ],
            [
                'courier_code'    => 'ghtk',
                'name'            => 'Giao Hàng Tiết Kiệm',
                'api_endpoint'    => 'https://services.giaohangtietkiem.vn',
                'pickup_hub_code' => null,
            ],
        ];

        $courier = $this->faker->randomElement($couriers);

        return [
            'courier_code'   => $courier['courier_code'],
            'name'           => $courier['name'],
            'api_endpoint'   => $courier['api_endpoint'],
            'api_key'        => $this->faker->sha256(),
            'api_secret'     => null,
            'account_id'     => $this->faker->numerify('#######'),
            'pickup_hub_code' => $courier['pickup_hub_code'],
            'pickup_address' => [
                'name'    => 'Kho Driip',
                'phone'   => '0901234567',
                'address' => '123 Nguyễn Huệ',
                'ward'    => 'Phường Bến Nghé',
                'district' => 'Quận 1',
                'province' => 'TP. Hồ Chí Minh',
            ],
            'webhook_secret' => $this->faker->sha256(),
            'is_active'      => true,
            'settings'       => [],
        ];
    }

    /**
     * State for GHN courier config.
     *
     * @return static
     */
    public function ghn(): static
    {
        return $this->state(fn (array $attributes) => [
            'courier_code' => 'ghn',
            'name'         => 'Giao Hàng Nhanh',
            'api_endpoint' => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
        ]);
    }

    /**
     * State for GHTK courier config.
     *
     * @return static
     */
    public function ghtk(): static
    {
        return $this->state(fn (array $attributes) => [
            'courier_code' => 'ghtk',
            'name'         => 'Giao Hàng Tiết Kiệm',
            'api_endpoint' => 'https://services.giaohangtietkiem.vn',
        ]);
    }
}
