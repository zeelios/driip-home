<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Coupon model instances.
 *
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type  = $this->faker->randomElement(['percent', 'fixed_amount', 'free_shipping']);
        $value = match ($type) {
            'percent'      => $this->faker->randomElement([5, 10, 15, 20]),
            'fixed_amount' => $this->faker->randomElement([20000, 50000, 100000]),
            'free_shipping' => 0,
            default        => 10,
        };

        $code = strtoupper($this->faker->bothify('????##'));

        return [
            'code'                  => $code,
            'name'                  => "Mã giảm giá {$code}",
            'description'           => $this->faker->optional(0.5)->sentence(),
            'type'                  => $type,
            'value'                 => $value,
            'min_order_amount'      => $this->faker->randomElement([null, 200000, 300000, 500000]),
            'min_items'             => null,
            'max_discount_amount'   => $type === 'percent' ? $this->faker->randomElement([null, 100000, 200000]) : null,
            'applies_to'            => 'all',
            'applies_to_ids'        => [],
            'max_uses'              => $this->faker->randomElement([null, 50, 100, 500]),
            'max_uses_per_customer' => 1,
            'used_count'            => 0,
            'is_public'             => true,
            'is_active'             => true,
            'starts_at'             => now(),
            'expires_at'            => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'created_by'            => null,
        ];
    }

    /**
     * State for a percentage discount coupon.
     *
     * @param  int  $percent
     * @return static
     */
    public function percentage(int $percent = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => 'percent',
            'value' => $percent,
        ]);
    }

    /**
     * State for a fixed VND discount coupon.
     *
     * @param  int  $amount
     * @return static
     */
    public function fixed(int $amount = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => 'fixed_amount',
            'value' => $amount,
        ]);
    }

    /**
     * State for a free shipping coupon.
     *
     * @return static
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => 'free_shipping',
            'value' => 0,
        ]);
    }

    /**
     * State for an expired coupon.
     *
     * @return static
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'  => false,
            'expires_at' => now()->subMonth(),
        ]);
    }
}
