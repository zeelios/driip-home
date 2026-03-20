<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Staff\Models\SalaryRecord;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating SalaryRecord model instances.
 *
 * Generates realistic Vietnamese salary data with base salary, allowances,
 * bonuses, deductions, and computed gross/net totals.
 *
 * @extends Factory<SalaryRecord>
 */
class SalaryRecordFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = SalaryRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year  = $this->faker->numberBetween(2023, 2024);
        $month = $this->faker->numberBetween(1, 12);
        $period = sprintf('%04d-%02d', $year, $month);

        $baseSalary = $this->faker->numberBetween(5000000, 20000000);

        $transportAllowance = $this->faker->randomElement([0, 500000, 800000, 1000000]);
        $mealAllowance      = $this->faker->randomElement([0, 500000, 800000]);
        $phoneAllowance     = $this->faker->randomElement([0, 200000, 300000]);
        $allowances         = [
            'transport' => $transportAllowance,
            'meal'      => $mealAllowance,
            'phone'     => $phoneAllowance,
        ];
        $totalAllowances = $transportAllowance + $mealAllowance + $phoneAllowance;

        $performanceBonus = $this->faker->randomElement([0, 500000, 1000000, 2000000, 3000000]);
        $holidayBonus     = $this->faker->randomElement([0, 500000, 1000000]);
        $bonuses          = [
            'performance' => $performanceBonus,
            'holiday'     => $holidayBonus,
        ];
        $totalBonuses = $performanceBonus + $holidayBonus;

        $socialInsurance  = (int) round($baseSalary * 0.08);
        $healthInsurance  = (int) round($baseSalary * 0.015);
        $unemployInsurance = (int) round($baseSalary * 0.01);
        $deductions       = [
            'social_insurance'      => $socialInsurance,
            'health_insurance'      => $healthInsurance,
            'unemployment_insurance' => $unemployInsurance,
        ];
        $totalDeductions = $socialInsurance + $healthInsurance + $unemployInsurance;

        $totalGross = $baseSalary + $totalAllowances + $totalBonuses;
        $totalNet   = $totalGross - $totalDeductions;

        return [
            'user_id'           => User::factory(),
            'period'            => $period,
            'base_salary'       => $baseSalary,
            'allowances'        => $allowances,
            'overtime_hours'    => '0.00',
            'overtime_rate'     => 0,
            'bonuses'           => $bonuses,
            'deductions'        => $deductions,
            'total_gross'       => $totalGross,
            'total_net'         => $totalNet,
            'paid_at'           => $this->faker->dateTimeBetween("-{$month} months", 'now'),
            'payment_method'    => 'bank_transfer',
            'payment_reference' => strtoupper($this->faker->bothify('PAY-????-#####')),
            'notes'             => null,
            'created_by'        => null,
        ];
    }
}
