<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Coupon\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Seed 3 coupons: WELCOME10, SALE50K, FREESHIP.
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Chào mừng khách hàng mới - Giảm 10%',
                'description' => 'Giảm 10% cho đơn hàng đầu tiên của khách hàng mới.',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 200000,
                'max_discount_amount' => 200000,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => null,
                'max_uses_per_customer' => 1,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'SALE50K',
                'name' => 'Giảm 50.000đ cho đơn từ 300.000đ',
                'description' => 'Giảm ngay 50.000 VND cho đơn hàng từ 300.000 VND.',
                'type' => 'fixed_amount',
                'value' => 50000,
                'min_order_amount' => 300000,
                'max_discount_amount' => null,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => 500,
                'max_uses_per_customer' => 2,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Miễn phí vận chuyển',
                'description' => 'Miễn phí vận chuyển cho mọi đơn hàng không giới hạn giá trị.',
                'type' => 'free_shipping',
                'value' => 0,
                'min_order_amount' => null,
                'max_discount_amount' => null,
                'applies_to' => 'all',
                'applies_to_ids' => [],
                'max_uses' => 200,
                'max_uses_per_customer' => 1,
                'used_count' => 0,
                'is_public' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon,
            );
        }
    }
}
