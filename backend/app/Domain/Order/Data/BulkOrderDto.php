<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for BulkOrderRequest.
 * Source of truth: validation rules in BulkOrderRequest.
 */
readonly class BulkOrderDto
{
    public function __construct(
        public array $orderIds = []
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderIds: $data['order_ids'] ?? []
        );
    }
}
