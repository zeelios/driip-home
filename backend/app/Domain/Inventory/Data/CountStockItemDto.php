<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Auto-generated DTO for CountStockItemRequest.
 * Source of truth: validation rules in CountStockItemRequest.
 */
readonly class CountStockItemDto
{
    public function __construct(
        public int $quantityCounted,
        public ?string $notes = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            quantityCounted: $data['quantity_counted'],
            notes: $data['notes'] ?? null
        );
    }
}
