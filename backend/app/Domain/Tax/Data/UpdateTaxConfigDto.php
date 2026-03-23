<?php

declare(strict_types=1);

namespace App\Domain\Tax\Data;

/**
 * Auto-generated DTO for UpdateTaxConfigRequest.
 * Source of truth: validation rules in UpdateTaxConfigRequest.
 */
readonly class UpdateTaxConfigDto
{
    public function __construct(
        public ?string $name = null,
        public ?float $rate = null,
        public ?string $appliesTo = null,
        public array $appliesToIds = [],
        public ?string $effectiveFrom = null,
        public ?string $effectiveTo = null,
        public ?bool $isActive = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            rate: $data['rate'] ?? null,
            appliesTo: $data['applies_to'] ?? null,
            appliesToIds: $data['applies_to_ids'] ?? [],
            effectiveFrom: $data['effective_from'] ?? null,
            effectiveTo: $data['effective_to'] ?? null,
            isActive: $data['is_active'] ?? null
        );
    }
}
