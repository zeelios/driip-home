<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Data transfer object for a manual inventory adjustment request.
 */
class AdjustInventoryDto
{
    /**
     * Create a new AdjustInventoryDto.
     *
     * @param  string  $variantId    UUID of the product variant to adjust.
     * @param  string  $warehouseId  UUID of the warehouse holding the stock.
     * @param  int     $quantity     Positive to add stock, negative to remove it.
     * @param  string  $reason       Human-readable reason for the adjustment.
     * @param  string  $createdBy    UUID of the staff user initiating the adjustment.
     */
    public function __construct(
        public readonly string $variantId,
        public readonly string $warehouseId,
        public readonly int    $quantity,
        public readonly string $reason,
        public readonly string $createdBy,
    ) {}
}
