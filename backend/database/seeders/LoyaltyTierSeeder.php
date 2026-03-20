<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Loyalty\Models\LoyaltyTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the four default loyalty tiers for the Driip loyalty programme.
 *
 * Tiers: Bronze (0 pts), Silver (1000 pts), Gold (5000 pts), Diamond (20000 pts).
 * Uses updateOrCreate keyed on slug to remain idempotent.
 */
class LoyaltyTierSeeder extends Seeder
{
    /**
     * Run the loyalty tiers seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $tiers = [
            [
                'name'                => 'Bronze',
                'slug'                => 'bronze',
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
                'slug'                => 'silver',
                'min_lifetime_points' => 1000,
                'discount_percent'    => '2.00',
                'free_shipping'       => false,
                'early_access'        => false,
                'birthday_multiplier' => '1.50',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 2% mỗi đơn hàng'],
                'color'               => '#C0C0C0',
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Gold',
                'slug'                => 'gold',
                'min_lifetime_points' => 5000,
                'discount_percent'    => '5.00',
                'free_shipping'       => true,
                'early_access'        => false,
                'birthday_multiplier' => '2.00',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 5% mỗi đơn hàng', 'Miễn phí vận chuyển'],
                'color'               => '#FFD700',
                'sort_order'          => 3,
            ],
            [
                'name'                => 'Diamond',
                'slug'                => 'diamond',
                'min_lifetime_points' => 20000,
                'discount_percent'    => '10.00',
                'free_shipping'       => true,
                'early_access'        => true,
                'birthday_multiplier' => '3.00',
                'perks'               => ['Tích điểm trên mỗi đơn hàng', 'Giảm giá 10% mỗi đơn hàng', 'Miễn phí vận chuyển', 'Truy cập sớm sản phẩm mới', 'Ưu tiên hỗ trợ khách hàng'],
                'color'               => '#B9F2FF',
                'sort_order'          => 4,
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier,
            );
        }
    }
}
