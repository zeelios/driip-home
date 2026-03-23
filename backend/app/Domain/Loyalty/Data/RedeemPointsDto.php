<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Data;

/**
 * Auto-generated DTO for RedeemPointsRequest.
 * Source of truth: validation rules in RedeemPointsRequest.
 */
readonly class RedeemPointsDto
{
    public function __construct(
        public int $points,
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
            referenceId: $data['reference_id'] ?? null,
            description: $data['description'] ?? null
        );
    }
}
