<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Warehouse model instances.
 *
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $warehouses = [
            [
                'code'     => 'WH-HN-001',
                'name'     => 'Kho Hà Nội',
                'province' => 'Hà Nội',
                'district' => 'Quận Long Biên',
                'address'  => '123 Nguyễn Văn Cừ, Quận Long Biên, Hà Nội',
            ],
            [
                'code'     => 'WH-HCM-001',
                'name'     => 'Kho TP.HCM',
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận Bình Tân',
                'address'  => '456 Kinh Dương Vương, Quận Bình Tân, TP. Hồ Chí Minh',
            ],
            [
                'code'     => 'WH-DN-001',
                'name'     => 'Kho Đà Nẵng',
                'province' => 'Đà Nẵng',
                'district' => 'Quận Cẩm Lệ',
                'address'  => '789 Cách Mạng Tháng 8, Quận Cẩm Lệ, Đà Nẵng',
            ],
        ];

        $warehouse = $warehouses[($counter - 1) % count($warehouses)];

        $phonePrefix = $this->faker->randomElement(['090', '091', '028', '024']);
        $phone       = $phonePrefix . $this->faker->numerify('#######');

        return [
            'code'       => $warehouse['code'],
            'name'       => $warehouse['name'],
            'type'       => 'main',
            'address'    => $warehouse['address'],
            'province'   => $warehouse['province'],
            'district'   => $warehouse['district'],
            'phone'      => $phone,
            'manager_id' => null,
            'is_active'  => true,
            'notes'      => null,
        ];
    }

    /**
     * State for a satellite warehouse.
     *
     * @return static
     */
    public function satellite(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'satellite',
        ]);
    }

    /**
     * State for a virtual warehouse.
     *
     * @return static
     */
    public function virtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'virtual',
        ]);
    }
}
