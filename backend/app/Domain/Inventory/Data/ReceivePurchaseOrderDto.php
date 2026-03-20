<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Data transfer object for recording goods received against a purchase order.
 */
class ReceivePurchaseOrderDto
{
    /**
     * Create a new ReceivePurchaseOrderDto.
     *
     * @param  string            $receivedBy    UUID of the staff user receiving the goods.
     * @param  array<int,mixed>  $receiptItems  Array of received items, each with: po_item_id, qty_received.
     * @param  string|null       $notes         Optional notes for the receipt.
     * @param  array<int,mixed>  $attachments   Optional document attachments.
     */
    public function __construct(
        public readonly string  $receivedBy,
        public readonly array   $receiptItems,
        public readonly ?string $notes = null,
        public readonly array   $attachments = [],
    ) {}
}
