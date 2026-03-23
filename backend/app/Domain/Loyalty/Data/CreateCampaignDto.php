<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Data;

/**
 * Auto-generated DTO for CreateCampaignRequest.
 * Source of truth: validation rules in CreateCampaignRequest.
 */
readonly class CreateCampaignDto
{
    public function __construct(
        public string $name,
        public string $type,
        public ?float $multiplier = null,
        public ?int $bonusPoints = null,
        public array $conditions = [],
        public string $startsAt,
        public ?string $endsAt = null,
        public ?bool $isActive = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            multiplier: $data['multiplier'] ?? null,
            bonusPoints: $data['bonus_points'] ?? null,
            conditions: $data['conditions'] ?? [],
            startsAt: $data['starts_at'],
            endsAt: $data['ends_at'] ?? null,
            isActive: $data['is_active'] ?? null
        );
    }
}
