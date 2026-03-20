<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\SaleEvent\Models\SaleEvent;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating SaleEvent model instances.
 *
 * @extends Factory<SaleEvent>
 */
class SaleEventFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = SaleEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['flash_sale', 'drop_launch', 'clearance', 'bundle'];
        $type  = $this->faker->randomElement($types);

        $names = [
            'flash_sale'  => ['Driip Flash Sale 11.11', 'Driip Mega Sale Cuối Năm', 'Flash Sale Cuối Tuần'],
            'drop_launch' => ['Driip SS25 Drop 01', 'Driip FW24 Collection Launch', 'Driip x Collab Drop'],
            'clearance'   => ['Thanh Lý Hàng Tồn Kho', 'Clear Out Sale', 'Hàng Cuối Mùa'],
            'bundle'      => ['Bundle Bộ Sưu Tập Hè', 'Combo Áo Thun + Quần Short', 'Bundle Tiết Kiệm'],
        ];

        $name      = $this->faker->randomElement($names[$type]);
        $startsAt  = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $endsAt    = (clone $startsAt)->modify('+' . $this->faker->numberBetween(1, 7) . ' days');

        return [
            'name'             => $name,
            'slug'             => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
            'description'      => $this->faker->optional(0.7)->sentence(),
            'type'             => $type,
            'status'           => 'draft',
            'starts_at'        => $startsAt,
            'ends_at'          => $endsAt,
            'max_orders_total' => $this->faker->optional(0.4)->numberBetween(50, 500),
            'is_public'        => true,
            'banner_url'       => null,
            'created_by'       => User::factory(),
        ];
    }

    /**
     * State for a scheduled sale event.
     *
     * @return static
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    /**
     * State for an active sale event.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'active',
            'starts_at' => now()->subHour(),
            'ends_at'   => now()->addDays(3),
        ]);
    }
}
