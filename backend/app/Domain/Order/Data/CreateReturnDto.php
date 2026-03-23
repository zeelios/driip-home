<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for CreateReturnRequest.
 * Source of truth: validation rules in CreateReturnRequest.
 */
readonly class CreateReturnDto
{
    public function __construct(
        public ?string $claimId = null,
        public array $returnItems = [],
        public ?string $notes = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            claimId: $data['claim_id'] ?? null,
            returnItems: $data['return_items'] ?? [],
            notes: $data['notes'] ?? null
        );
    }
}
