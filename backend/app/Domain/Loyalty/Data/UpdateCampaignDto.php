<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Data;

/**
 * Auto-generated DTO for UpdateCampaignRequest.
 * Source of truth: validation rules in UpdateCampaignRequest.
 */
readonly class UpdateCampaignDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?float $multiplier = null,
        public ?int $bonusPoints = null,
        public array $conditions = [],
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?bool $isActive = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            multiplier: $data['multiplier'] ?? null,
            bonusPoints: $data['bonus_points'] ?? null,
            conditions: $data['conditions'] ?? [],
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
            isActive: $data['is_active'] ?? null
        );
    }
}
