<?php

declare(strict_types=1);

namespace App\Domain\Tax\Data;

/**
 * Data Transfer Object for creating a new tax rate configuration.
 *
 * Carries all fields required to define a new tax rate for a given
 * effective period. At least one of applies_to_ids must be provided
 * when applies_to is 'category' or 'product'.
 */
readonly class CreateTaxConfigDto
{
    /**
     * Create a new CreateTaxConfigDto.
     *
     * @param  string          $name          Human-readable name for this configuration (e.g. "VAT 10%").
     * @param  float           $rate          Tax rate as a percentage (e.g. 10.0 for 10%).
     * @param  string          $appliesTo     Scope: 'all', 'category', or 'product'.
     * @param  list<string>    $appliesToIds  IDs of categories or products this rate applies to.
     * @param  string          $effectiveFrom ISO date string (YYYY-MM-DD) when this rate takes effect.
     * @param  string|null     $effectiveTo   ISO date string (YYYY-MM-DD) when this rate expires, or null for open-ended.
     * @param  bool            $isActive      Whether this configuration is currently active.
     */
    public function __construct(
        public string  $name,
        public float   $rate,
        public string  $appliesTo,
        public array   $appliesToIds,
        public string  $effectiveFrom,
        public ?string $effectiveTo,
        public bool    $isActive,
    ) {}

    /**
     * Build from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:          (string) $data['name'],
            rate:          (float) $data['rate'],
            appliesTo:     (string) ($data['applies_to'] ?? 'all'),
            appliesToIds:  (array) ($data['applies_to_ids'] ?? []),
            effectiveFrom: (string) $data['effective_from'],
            effectiveTo:   isset($data['effective_to']) ? (string) $data['effective_to'] : null,
            isActive:      (bool) ($data['is_active'] ?? true),
        );
    }
}
