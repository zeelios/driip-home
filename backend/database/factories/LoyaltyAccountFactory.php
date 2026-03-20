<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customer\Models\Customer;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating LoyaltyAccount model instances.
 *
 * @extends Factory<LoyaltyAccount>
 */
class LoyaltyAccountFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = LoyaltyAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lifetimeSpending = $this->faker->numberBetween(0, 50000000);
        $lifetimePoints   = (int) floor($lifetimeSpending / 1000);
        $pointsBalance    = $this->faker->numberBetween(0, $lifetimePoints);

        return [
            'customer_id'      => Customer::factory(),
            'tier_id'          => null,
            'points_balance'   => $pointsBalance,
            'lifetime_points'  => $lifetimePoints,
            'lifetime_spending' => $lifetimeSpending,
            'tier_achieved_at' => $lifetimePoints > 0 ? $this->faker->dateTimeBetween('-2 years', 'now') : null,
            'tier_expires_at'  => null,
        ];
    }

    /**
     * State for a new customer with zero points.
     *
     * @return static
     */
    public function fresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'points_balance'   => 0,
            'lifetime_points'  => 0,
            'lifetime_spending' => 0,
            'tier_id'          => null,
            'tier_achieved_at' => null,
        ]);
    }
}
