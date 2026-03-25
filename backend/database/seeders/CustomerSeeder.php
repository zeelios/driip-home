<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerAddress;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed 20 customers with loyalty accounts and addresses.
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $bronzeTier = LoyaltyTier::where('slug', 'bronze')->first();
        $silverTier = LoyaltyTier::where('slug', 'silver')->first();
        $goldTier = LoyaltyTier::where('slug', 'gold')->first();
        $diamondTier = LoyaltyTier::where('slug', 'diamond')->first();

        $customerDefs = [
            ['first_name' => 'Nguyễn', 'last_name' => 'Văn An', 'phone' => '0901100001', 'email' => 'van.an@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Trần', 'last_name' => 'Thị Lan', 'phone' => '0901100002', 'email' => 'thi.lan@example.com', 'tier' => $silverTier, 'spending' => 2500000],
            ['first_name' => 'Lê', 'last_name' => 'Minh Đức', 'phone' => '0901100003', 'email' => 'minh.duc@example.com', 'tier' => $goldTier, 'spending' => 8000000],
            ['first_name' => 'Phạm', 'last_name' => 'Thị Hoa', 'phone' => '0901100004', 'email' => 'thi.hoa@example.com', 'tier' => $bronzeTier, 'spending' => 500000],
            ['first_name' => 'Hoàng', 'last_name' => 'Quốc Huy', 'phone' => '0901100005', 'email' => 'quoc.huy@example.com', 'tier' => $diamondTier, 'spending' => 35000000],
            ['first_name' => 'Huỳnh', 'last_name' => 'Thị Mai', 'phone' => '0901100006', 'email' => 'thi.mai@example.com', 'tier' => $silverTier, 'spending' => 1800000],
            ['first_name' => 'Phan', 'last_name' => 'Văn Bình', 'phone' => '0901100007', 'email' => 'van.binh@example.com', 'tier' => $bronzeTier, 'spending' => 300000],
            ['first_name' => 'Vũ', 'last_name' => 'Ngọc Ánh', 'phone' => '0901100008', 'email' => 'ngoc.anh@example.com', 'tier' => $goldTier, 'spending' => 12000000],
            ['first_name' => 'Đặng', 'last_name' => 'Trung Kiên', 'phone' => '0901100009', 'email' => 'trung.kien@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Bùi', 'last_name' => 'Thị Thu', 'phone' => '0901100010', 'email' => 'thi.thu@example.com', 'tier' => $silverTier, 'spending' => 2000000],
            ['first_name' => 'Đỗ', 'last_name' => 'Anh Tuấn', 'phone' => '0901100011', 'email' => null, 'tier' => $bronzeTier, 'spending' => 150000],
            ['first_name' => 'Hồ', 'last_name' => 'Kim Oanh', 'phone' => '0901100012', 'email' => 'kim.oanh@example.com', 'tier' => $goldTier, 'spending' => 6500000],
            ['first_name' => 'Ngô', 'last_name' => 'Đức Long', 'phone' => '0901100013', 'email' => 'duc.long@example.com', 'tier' => $bronzeTier, 'spending' => 800000],
            ['first_name' => 'Dương', 'last_name' => 'Bích Ngọc', 'phone' => '0901100014', 'email' => 'bich.ngoc@example.com', 'tier' => $silverTier, 'spending' => 3200000],
            ['first_name' => 'Lý', 'last_name' => 'Thanh Tùng', 'phone' => '0901100015', 'email' => 'thanh.tung@example.com', 'tier' => $bronzeTier, 'spending' => 0],
            ['first_name' => 'Trịnh', 'last_name' => 'Minh Châu', 'phone' => '0901100016', 'email' => null, 'tier' => $bronzeTier, 'spending' => 600000],
            ['first_name' => 'Đinh', 'last_name' => 'Hữu Nam', 'phone' => '0901100017', 'email' => 'huu.nam@example.com', 'tier' => $goldTier, 'spending' => 9000000],
            ['first_name' => 'Mai', 'last_name' => 'Văn Phong', 'phone' => '0901100018', 'email' => 'van.phong@example.com', 'tier' => $bronzeTier, 'spending' => 1200000],
            ['first_name' => 'Cao', 'last_name' => 'Thị Hương', 'phone' => '0901100019', 'email' => 'thi.huong@example.com', 'tier' => $silverTier, 'spending' => 4500000],
            ['first_name' => 'Tô', 'last_name' => 'Quốc Khánh', 'phone' => '0901100020', 'email' => 'quoc.khanh@example.com', 'tier' => $diamondTier, 'spending' => 25000000],
        ];

        $sources = ['facebook', 'instagram', 'tiktok', 'website', 'referral', 'walk_in'];
        $provinces = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương'];
        $districts = ['Quận 1', 'Quận 3', 'Quận Hoàn Kiếm', 'Quận Đống Đa', 'Quận Hải Châu'];
        $streets = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Điện Biên Phủ', 'Cách Mạng Tháng 8'];

        foreach ($customerDefs as $i => $def) {
            $lifetimeSpending = $def['spending'];
            $lifetimePoints = (int) floor($lifetimeSpending / 1000);
            $totalOrders = $lifetimeSpending > 0 ? rand(1, max(1, (int) ($lifetimeSpending / 500000))) : 0;

            $customer = Customer::updateOrCreate(
                ['phone' => $def['phone']],
                [
                    'customer_code' => sprintf('DRP-C-%05d', $i + 1),
                    'first_name' => $def['first_name'],
                    'last_name' => $def['last_name'],
                    'email' => $def['email'],
                    'phone' => $def['phone'],
                    'gender' => $i % 3 === 0 ? 'male' : ($i % 3 === 1 ? 'female' : null),
                    'source' => $sources[$i % count($sources)],
                    'tags' => $lifetimeSpending > 20000000 ? ['vip'] : ($lifetimeSpending > 5000000 ? ['regular'] : ['new']),
                    'is_blocked' => false,
                    'total_orders' => $totalOrders,
                    'total_spent' => $lifetimeSpending,
                    'last_ordered_at' => $totalOrders > 0 ? now()->subDays(rand(1, 90)) : null,
                    'referral_code' => Str::upper(Str::substr(Str::slug($def['first_name'], ''), 0, 3) . sprintf('%04d', $i + 1)),
                ],
            );

            // Create loyalty account
            $tier = $def['tier'];
            LoyaltyAccount::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'tier_id' => $tier?->id,
                    'points_balance' => $lifetimePoints,
                    'lifetime_points' => $lifetimePoints,
                    'lifetime_spending' => $lifetimeSpending,
                    'tier_achieved_at' => $lifetimePoints > 0 ? now()->subMonths(rand(1, 12)) : null,
                ],
            );

            // Create default address
            $province = $provinces[$i % count($provinces)];
            $district = $districts[$i % count($districts)];
            $street = $streets[$i % count($streets)];

            CustomerAddress::updateOrCreate(
                ['customer_id' => $customer->id, 'is_default' => true],
                [
                    'label' => 'Nhà',
                    'recipient_name' => $def['first_name'] . ' ' . $def['last_name'],
                    'phone' => $def['phone'],
                    'province' => $province,
                    'district' => $district,
                    'ward' => 'Phường ' . rand(1, 20),
                    'address' => rand(1, 300) . ' ' . $street,
                    'is_default' => true,
                ],
            );
        }
    }
}
