<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventory\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Seed 3 suppliers.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $supplierData = [
            [
                'code' => 'DRP-SUP-001',
                'name' => 'Công ty TNHH Dệt May Thành Công',
                'contact_name' => 'Nguyễn Văn Thành',
                'email' => 'thanh@detmaythanhcong.vn',
                'phone' => '0901111111',
                'address' => '123 Trường Chinh, Quận Tân Bình, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'NET30',
                'is_active' => true,
            ],
            [
                'code' => 'DRP-SUP-002',
                'name' => 'Xưởng May Thời Trang Minh Châu',
                'contact_name' => 'Trần Thị Châu',
                'email' => 'chau@minhchau.vn',
                'phone' => '0902222222',
                'address' => '456 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'NET15',
                'is_active' => true,
            ],
            [
                'code' => 'DRP-SUP-003',
                'name' => 'Công ty CP Sản Xuất Phụ Kiện Bình Minh',
                'contact_name' => 'Lê Quốc Huy',
                'email' => 'huy@binhminh.vn',
                'phone' => '0903333333',
                'address' => '789 Hòa Bình, Quận Tân Phú, TP. Hồ Chí Minh',
                'province' => 'TP. Hồ Chí Minh',
                'country' => 'VN',
                'payment_terms' => 'COD',
                'is_active' => true,
            ],
        ];

        foreach ($supplierData as $data) {
            Supplier::updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }
    }
}
