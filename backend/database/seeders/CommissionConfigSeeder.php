<?php

namespace Database\Seeders;

use App\Domain\Commission\Models\CommissionConfig;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed default commission configurations for sales staff.
 */
class CommissionConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Default 5% rate for all staff (can be overridden per staff)
        $defaultRate = 5.0;

        // Map referral codes to expected employee codes
        $referralMappings = [
            'kn' => ['name' => 'Kim Ngoc', 'code' => 'KN'],
            'pa' => ['name' => 'Phuong Anh', 'code' => 'PA'],
            'ze' => ['name' => 'Zeelios', 'code' => 'ZE'],
        ];

        foreach ($referralMappings as $referralCode => $mapping) {
            // Try to find staff by employee_code
            $staff = User::where('employee_code', $mapping['code'])
                ->orWhere('employee_code', strtolower($mapping['code']))
                ->orWhere('name', 'like', "%{$mapping['name']}%")
                ->first();

            if ($staff) {
                // Check if config already exists
                $existing = CommissionConfig::where('staff_id', $staff->id)
                    ->where('is_active', true)
                    ->first();

                if (!$existing) {
                    CommissionConfig::create([
                        'staff_id'       => $staff->id,
                        'rate_percent'   => $defaultRate,
                        'category_rates' => [],
                        'effective_from' => now()->toDateString(),
                        'effective_to'   => null,
                        'is_active'      => true,
                    ]);
                }
            }
        }

        // Create configs for any other sales staff found
        $salesStaff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['sales-staff', 'manager', 'admin']);
        })->get();

        foreach ($salesStaff as $staff) {
            $existing = CommissionConfig::where('staff_id', $staff->id)
                ->where('is_active', true)
                ->first();

            if (!$existing) {
                CommissionConfig::create([
                    'staff_id'       => $staff->id,
                    'rate_percent'   => $defaultRate,
                    'category_rates' => [],
                    'effective_from' => now()->toDateString(),
                    'effective_to'   => null,
                    'is_active'      => true,
                ]);
            }
        }
    }
}
