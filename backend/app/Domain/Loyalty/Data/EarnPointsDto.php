<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Data;

/**
 * Auto-generated DTO for EarnPointsRequest.
 * Source of truth: validation rules in EarnPointsRequest.
 */
readonly class EarnPointsDto
{
    public function __construct(
        public int $points,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $description = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            points: $data['points'],
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            description: $data['description'] ?? null
        );
    }
}
