<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shipment\Models\CourierConfig;
use Illuminate\Database\Seeder;

/**
 * Seed GHN and GHTK courier configurations with placeholder credentials.
 */
class CourierConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'courier_code' => 'ghn',
                'name' => 'Giao Hàng Nhanh',
                'api_endpoint' => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
                'api_key' => 'placeholder_ghn_api_key',
                'api_secret' => null,
                'account_id' => '12345',
                'pickup_hub_code' => 'SGBQ',
                'pickup_address' => [
                    'name' => 'Kho Driip HCM',
                    'phone' => '0901234567',
                    'address' => '123 Nguyễn Huệ',
                    'ward' => 'Phường Bến Nghé',
                    'district' => 'Quận 1',
                    'province' => 'TP. Hồ Chí Minh',
                ],
                'webhook_secret' => 'placeholder_ghn_webhook_secret',
                'is_active' => true,
                'settings' => ['service_type_id' => 2],
            ],
            [
                'courier_code' => 'ghtk',
                'name' => 'Giao Hàng Tiết Kiệm',
                'api_endpoint' => 'https://services.giaohangtietkiem.vn',
                'api_key' => 'placeholder_ghtk_api_key',
                'api_secret' => null,
                'account_id' => '67890',
                'pickup_hub_code' => null,
                'pickup_address' => [
                    'name' => 'Kho Driip HCM',
                    'phone' => '0901234567',
                    'address' => '123 Nguyễn Huệ, Phường Bến Nghé, Quận 1',
                    'province' => 'TP. Hồ Chí Minh',
                ],
                'webhook_secret' => 'placeholder_ghtk_webhook_secret',
                'is_active' => true,
                'settings' => [],
            ],
        ];

        foreach ($configs as $config) {
            CourierConfig::updateOrCreate(
                ['courier_code' => $config['courier_code']],
                $config,
            );
        }
    }
}
