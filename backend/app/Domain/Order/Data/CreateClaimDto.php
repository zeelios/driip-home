<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for CreateClaimRequest.
 * Source of truth: validation rules in CreateClaimRequest.
 */
readonly class CreateClaimDto
{
    public function __construct(
        public string $type,
        public string $description,
        public array $evidenceUrls = [],
        public ?string $orderItemId = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            description: $data['description'],
            evidenceUrls: $data['evidence_urls'] ?? [],
            orderItemId: $data['order_item_id'] ?? null
        );
    }
}
