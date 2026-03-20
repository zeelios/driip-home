<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Settings\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the settings table with default application configuration values.
 *
 * Covers the following groups:
 *  - loyalty:  points earn/redeem rates and minimum thresholds
 *  - tax:      default VAT rate and invoice prefix
 *  - shipping: free-shipping threshold and default courier
 *  - order:    auto-cancel window and COD limit
 *  - invoice:  invoice numbering and company details
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the settings seeder.
     *
     * Each row is upserted by (group, key) so the seeder is idempotent.
     *
     * @return void
     */
    public function run(): void
    {
        $defaults = [
            // Loyalty
            [
                'group' => 'loyalty',
                'key'   => 'points_per_vnd',
                'value' => '1000',
                'type'  => 'integer',
                'label' => 'Điểm trên mỗi 1000 VND',
            ],
            [
                'group' => 'loyalty',
                'key'   => 'redeem_rate',
                'value' => '1000',
                'type'  => 'integer',
                'label' => 'Giá trị quy đổi điểm (VND)',
            ],
            [
                'group' => 'loyalty',
                'key'   => 'min_redeem_points',
                'value' => '100',
                'type'  => 'integer',
                'label' => 'Điểm tối thiểu để đổi',
            ],

            // Tax
            [
                'group' => 'tax',
                'key'   => 'default_vat_rate',
                'value' => '10',
                'type'  => 'integer',
                'label' => 'Thuế GTGT mặc định (%)',
            ],
            [
                'group' => 'tax',
                'key'   => 'vat_invoice_prefix',
                'value' => 'INV',
                'type'  => 'string',
                'label' => 'Tiền tố số hóa đơn VAT',
            ],

            // Shipping
            [
                'group' => 'shipping',
                'key'   => 'free_shipping_threshold',
                'value' => '500000',
                'type'  => 'integer',
                'label' => 'Đơn hàng miễn phí vận chuyển (VND)',
            ],
            [
                'group' => 'shipping',
                'key'   => 'default_courier',
                'value' => 'ghn',
                'type'  => 'string',
                'label' => 'Đơn vị vận chuyển mặc định',
            ],

            // Order
            [
                'group' => 'order',
                'key'   => 'auto_cancel_unpaid_after_hours',
                'value' => '24',
                'type'  => 'integer',
                'label' => 'Tự hủy đơn chưa thanh toán sau (giờ)',
            ],
            [
                'group' => 'order',
                'key'   => 'max_cod_amount',
                'value' => '5000000',
                'type'  => 'integer',
                'label' => 'COD tối đa (VND)',
            ],

            // Invoice
            [
                'group' => 'invoice',
                'key'   => 'invoice_number_prefix',
                'value' => 'INV',
                'type'  => 'string',
                'label' => 'Tiền tố số hóa đơn',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_name',
                'value' => 'Driip Brand',
                'type'  => 'string',
                'label' => 'Tên công ty',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_tax_code',
                'value' => '0123456789',
                'type'  => 'string',
                'label' => 'Mã số thuế công ty',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_address',
                'value' => '123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
                'type'  => 'string',
                'label' => 'Địa chỉ công ty',
            ],
        ];

        foreach ($defaults as $row) {
            Setting::updateOrInsert(
                ['group' => $row['group'], 'key' => $row['key']],
                [
                    'id'    => (string) Str::uuid(),
                    'value' => $row['value'],
                    'type'  => $row['type'],
                    'label' => $row['label'],
                ],
            );
        }
    }
}
