<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Data Transfer Object for a single line item in a new order.
 *
 * Carries the minimal fields needed to identify the variant being
 * purchased and the agreed price at checkout time.
 */
readonly class CreateOrderItemDto
{
    /**
     * Create a new CreateOrderItemDto.
     *
     * @param  string  $productVariantId  UUID of the product variant.
     * @param  int     $quantity          Number of units ordered (minimum 1).
     * @param  int     $unitPrice         Agreed unit price in VND (minor units).
     */
    public function __construct(
        public string $productVariantId,
        public int    $quantity,
        public int    $unitPrice,
    ) {}
}
