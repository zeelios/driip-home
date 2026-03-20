<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Data transfer object for creating a new stock transfer request.
 */
class CreateStockTransferDto
{
    /**
     * Create a new CreateStockTransferDto.
     *
     * @param  string            $fromWarehouseId  UUID of the source warehouse.
     * @param  string            $toWarehouseId    UUID of the destination warehouse.
     * @param  array<int,mixed>  $items            Array of items: product_variant_id, quantity_requested, notes?.
     * @param  string            $requestedBy      UUID of the staff user requesting the transfer.
     * @param  string|null       $reason           Reason for the transfer.
     * @param  string|null       $notes            Optional notes.
     */
    public function __construct(
        public readonly string  $fromWarehouseId,
        public readonly string  $toWarehouseId,
        public readonly array   $items,
        public readonly string  $requestedBy,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
    ) {}
}
