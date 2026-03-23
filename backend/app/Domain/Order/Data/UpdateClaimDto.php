<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for UpdateClaimRequest.
 * Source of truth: validation rules in UpdateClaimRequest.
 */
readonly class UpdateClaimDto
{
    public function __construct(
        public ?string $status = null,
        public ?string $resolution = null,
        public ?string $resolutionNotes = null,
        public ?int $refundAmount = null,
        public ?string $assignedTo = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            resolution: $data['resolution'] ?? null,
            resolutionNotes: $data['resolution_notes'] ?? null,
            refundAmount: $data['refund_amount'] ?? null,
            assignedTo: $data['assigned_to'] ?? null
        );
    }
}
