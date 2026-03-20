<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Data transfer object for creating a new stock count task.
 */
class CreateStockCountDto
{
    /**
     * Create a new CreateStockCountDto.
     *
     * @param  string            $warehouseId  UUID of the warehouse to count.
     * @param  string            $type         Count type: full, partial, cycle_count, or spot_check.
     * @param  string            $createdBy    UUID of the staff user creating the count.
     * @param  string|null       $scheduledAt  Scheduled date for the count (Y-m-d).
     * @param  string|null       $notes        Optional notes.
     * @param  array<int,string> $variantIds   Optional list of specific variant UUIDs to count (empty = all).
     */
    public function __construct(
        public readonly string  $warehouseId,
        public readonly string  $type,
        public readonly string  $createdBy,
        public readonly ?string $scheduledAt = null,
        public readonly ?string $notes = null,
        public readonly array   $variantIds = [],
    ) {}
}
