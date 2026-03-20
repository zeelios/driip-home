<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Tax\Models\TaxConfig;
use Illuminate\Database\Seeder;

/**
 * Seeds the tax configuration records for the Driip platform.
 *
 * Creates two records:
 *  - VAT 10%: active, effective from 2024-01-01, open-ended (current standard rate)
 *  - VAT 8%:  historical record, effective 2023-01-01 to 2023-12-31 (stimulus reduction period)
 *
 * Uses updateOrCreate keyed on (name, effective_from) to remain idempotent.
 */
class TaxConfigSeeder extends Seeder
{
    /**
     * Run the tax config seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $configs = [
            [
                'name'            => 'Thuế GTGT 10%',
                'rate'            => '10.00',
                'applies_to'      => null,
                'applies_to_ids'  => [],
                'effective_from'  => '2024-01-01',
                'effective_to'    => null,
                'is_active'       => true,
                'created_at'      => now(),
            ],
            [
                'name'            => 'Thuế GTGT 8% (Giảm thuế kích cầu)',
                'rate'            => '8.00',
                'applies_to'      => null,
                'applies_to_ids'  => [],
                'effective_from'  => '2023-01-01',
                'effective_to'    => '2023-12-31',
                'is_active'       => false,
                'created_at'      => '2023-01-01 00:00:00',
            ],
        ];

        foreach ($configs as $config) {
            TaxConfig::updateOrCreate(
                [
                    'name'           => $config['name'],
                    'effective_from' => $config['effective_from'],
                ],
                $config,
            );
        }
    }
}
