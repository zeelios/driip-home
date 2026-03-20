<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Data;

/**
 * Data transfer object for creating a new purchase order.
 */
class CreatePurchaseOrderDto
{
    /**
     * Create a new CreatePurchaseOrderDto.
     *
     * @param  string                $supplierId        UUID of the supplier.
     * @param  string                $warehouseId       UUID of the destination warehouse.
     * @param  array<int,mixed>      $items             Array of line items, each with: product_variant_id, quantity_ordered, unit_cost, notes?.
     * @param  string                $createdBy         UUID of the staff user creating the PO.
     * @param  string|null           $expectedArrivalAt Expected arrival date (Y-m-d).
     * @param  int|null              $shippingCost      Shipping cost in VND.
     * @param  int|null              $otherCosts        Other additional costs in VND.
     * @param  string|null           $notes             Optional notes.
     */
    public function __construct(
        public readonly string  $supplierId,
        public readonly string  $warehouseId,
        public readonly array   $items,
        public readonly string  $createdBy,
        public readonly ?string $expectedArrivalAt = null,
        public readonly ?int    $shippingCost = null,
        public readonly ?int    $otherCosts = null,
        public readonly ?string $notes = null,
    ) {}
}
