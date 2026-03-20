<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Loyalty\Models\LoyaltyTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating LoyaltyTier model instances.
 *
 * @extends Factory<LoyaltyTier>
 */
class LoyaltyTierFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = LoyaltyTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tiers = [
            [
                'name'                => 'Bronze',
                'min_lifetime_points' => 0,
                'discount_percent'    => '0.00',
                'free_shipping'       => false,
                'early_access'        => false,
                'birthday_multiplier' => '1.00',
                'perks'               => ['Tích điểm trên mỗi đơn hàng'],
                'color'               => '#CD7F32',
                'sort_order'          => 1,
            ],
            [
                'name'                => 'Silver',
                'min_lifetime_points' => 1000,
                'discount_percent'    => '2.00',
                'free_shipping'       => false,
                'early_access'        => false,
                'birthday_multiplier' => '1.50',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 2% mỗi đơn'],
                'color'               => '#C0C0C0',
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Gold',
                'min_lifetime_points' => 5000,
                'discount_percent'    => '5.00',
                'free_shipping'       => true,
                'early_access'        => false,
                'birthday_multiplier' => '2.00',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 5% mỗi đơn', 'Miễn phí vận chuyển'],
                'color'               => '#FFD700',
                'sort_order'          => 3,
            ],
            [
                'name'                => 'Diamond',
                'min_lifetime_points' => 20000,
                'discount_percent'    => '10.00',
                'free_shipping'       => true,
                'early_access'        => true,
                'birthday_multiplier' => '3.00',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 10% mỗi đơn', 'Miễn phí vận chuyển', 'Truy cập sớm sản phẩm mới'],
                'color'               => '#B9F2FF',
                'sort_order'          => 4,
            ],
        ];

        $tier = $this->faker->randomElement($tiers);

        return [
            'name'                => $tier['name'],
            'slug'                => Str::slug($tier['name']) . '-' . $this->faker->unique()->numerify('###'),
            'min_lifetime_points' => $tier['min_lifetime_points'],
            'discount_percent'    => $tier['discount_percent'],
            'free_shipping'       => $tier['free_shipping'],
            'early_access'        => $tier['early_access'],
            'birthday_multiplier' => $tier['birthday_multiplier'],
            'perks'               => $tier['perks'],
            'color'               => $tier['color'],
            'sort_order'          => $tier['sort_order'],
        ];
    }
}
