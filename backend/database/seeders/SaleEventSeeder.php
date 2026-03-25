<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\SaleEvent\Models\SaleEvent;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed 1 scheduled flash sale and 1 draft drop launch.
 */
class SaleEventSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@driip.com')->first();

        $events = [
            [
                'name' => 'Driip Flash Sale Hè 2025',
                'slug' => 'driip-flash-sale-he-2025',
                'description' => 'Flash sale lớn nhất hè 2025 — giảm giá lên đến 50% toàn bộ sản phẩm.',
                'type' => 'flash_sale',
                'status' => 'scheduled',
                'starts_at' => now()->addDays(7),
                'ends_at' => now()->addDays(8),
                'max_orders_total' => 300,
                'is_public' => true,
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Driip SS25 Drop 01',
                'slug' => 'driip-ss25-drop-01',
                'description' => 'Bộ sưu tập mùa hè 2025 chính thức ra mắt — limited edition.',
                'type' => 'drop_launch',
                'status' => 'draft',
                'starts_at' => now()->addDays(30),
                'ends_at' => now()->addDays(37),
                'max_orders_total' => null,
                'is_public' => false,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($events as $event) {
            SaleEvent::updateOrCreate(
                ['slug' => $event['slug']],
                $event,
            );
        }
    }
}
