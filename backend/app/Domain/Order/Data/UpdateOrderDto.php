<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for UpdateOrderRequest.
 * Source of truth: validation rules in UpdateOrderRequest.
 */
readonly class UpdateOrderDto
{
    public function __construct(
        public ?string $notes = null,
        public ?string $internalNotes = null,
        public ?string $assignedTo = null,
        public array $tags = []
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            notes: $data['notes'] ?? null,
            internalNotes: $data['internal_notes'] ?? null,
            assignedTo: $data['assigned_to'] ?? null,
            tags: $data['tags'] ?? []
        );
    }
}
