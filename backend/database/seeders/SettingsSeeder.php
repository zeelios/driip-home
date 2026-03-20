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
                'label' => 'Points earned per VND spent',
            ],
            [
                'group' => 'loyalty',
                'key'   => 'redeem_rate',
                'value' => '1000',
                'type'  => 'integer',
                'label' => 'VND value of one redeemed point',
            ],
            [
                'group' => 'loyalty',
                'key'   => 'min_redeem_points',
                'value' => '10000',
                'type'  => 'integer',
                'label' => 'Minimum points required to redeem',
            ],

            // Tax
            [
                'group' => 'tax',
                'key'   => 'default_vat_rate',
                'value' => '8.00',
                'type'  => 'float',
                'label' => 'Default VAT rate (%)',
            ],
            [
                'group' => 'tax',
                'key'   => 'vat_invoice_prefix',
                'value' => 'INV',
                'type'  => 'string',
                'label' => 'Prefix for VAT invoice numbers',
            ],

            // Shipping
            [
                'group' => 'shipping',
                'key'   => 'free_shipping_threshold',
                'value' => '500000',
                'type'  => 'integer',
                'label' => 'Order value threshold for free shipping (VND)',
            ],
            [
                'group' => 'shipping',
                'key'   => 'default_courier',
                'value' => 'ghn',
                'type'  => 'string',
                'label' => 'Default courier service slug',
            ],

            // Order
            [
                'group' => 'order',
                'key'   => 'auto_cancel_unpaid_after_hours',
                'value' => '48',
                'type'  => 'integer',
                'label' => 'Hours before an unpaid order is automatically cancelled',
            ],
            [
                'group' => 'order',
                'key'   => 'max_cod_amount',
                'value' => '10000000',
                'type'  => 'integer',
                'label' => 'Maximum order value eligible for Cash on Delivery (VND)',
            ],

            // Invoice
            [
                'group' => 'invoice',
                'key'   => 'invoice_number_prefix',
                'value' => 'INV',
                'type'  => 'string',
                'label' => 'Prefix used when generating invoice numbers',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_name',
                'value' => 'Driip',
                'type'  => 'string',
                'label' => 'Company name printed on invoices',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_tax_code',
                'value' => '',
                'type'  => 'string',
                'label' => 'Company tax identification number',
            ],
            [
                'group' => 'invoice',
                'key'   => 'company_address',
                'value' => '',
                'type'  => 'string',
                'label' => 'Company address printed on invoices',
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
