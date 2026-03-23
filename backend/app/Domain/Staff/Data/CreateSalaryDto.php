<?php

declare(strict_types=1);

namespace App\Domain\Staff\Data;

/**
 * Auto-generated DTO for CreateSalaryRequest.
 * Source of truth: validation rules in CreateSalaryRequest.
 */
readonly class CreateSalaryDto
{
    public function __construct(
        public string $period,
        public int $baseSalary,
        public array $allowances = [],
        public array $bonuses = [],
        public array $deductions = [],
        public ?float $overtimeHours = null,
        public ?int $overtimeRate = null,
        public ?string $paymentMethod = null,
        public ?string $paymentReference = null,
        public ?string $paidAt = null,
        public ?string $notes = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'],
            baseSalary: $data['base_salary'],
            allowances: $data['allowances'] ?? [],
            bonuses: $data['bonuses'] ?? [],
            deductions: $data['deductions'] ?? [],
            overtimeHours: $data['overtime_hours'] ?? null,
            overtimeRate: $data['overtime_rate'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            paymentReference: $data['payment_reference'] ?? null,
            paidAt: $data['paid_at'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
