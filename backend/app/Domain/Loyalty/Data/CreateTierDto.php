<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Data;

/**
 * Auto-generated DTO for CreateTierRequest.
 * Source of truth: validation rules in CreateTierRequest.
 */
readonly class CreateTierDto
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public int $minLifetimePoints,
        public float $discountPercent,
        public ?bool $freeShipping = null,
        public ?bool $earlyAccess = null,
        public ?float $birthdayMultiplier = null,
        public array $perks = [],
        public ?string $color = null,
        public ?int $sortOrder = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? null,
            minLifetimePoints: $data['min_lifetime_points'],
            discountPercent: $data['discount_percent'],
            freeShipping: $data['free_shipping'] ?? null,
            earlyAccess: $data['early_access'] ?? null,
            birthdayMultiplier: $data['birthday_multiplier'] ?? null,
            perks: $data['perks'] ?? [],
            color: $data['color'] ?? null,
            sortOrder: $data['sort_order'] ?? null
        );
    }
}
