<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Seed 2 warehouses: Kho Hà Nội and Kho TP.HCM.
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'WH-HN-001',
                'name' => 'Kho Hà Nội',
                'type' => 'main',
                'address' => '123 Nguyễn Văn Cừ, Quận Long Biên',
                'province' => 'Hà Nội',
                'district' => 'Quận Long Biên',
                'phone' => '02412345678',
                'is_active' => true,
            ],
            [
                'code' => 'WH-HCM-001',
                'name' => 'Kho TP.HCM',
                'type' => 'main',
                'address' => '456 Kinh Dương Vương, Quận Bình Tân',
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận Bình Tân',
                'phone' => '02812345678',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                $warehouse,
            );
        }
    }
}
