<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a stock operation requests more units than are available.
 *
 * Carries the variant ID, requested quantity, and currently available quantity
 * so callers can build meaningful error messages for the UI or logs.
 */
class InsufficientStockException extends RuntimeException
{
    /**
     * Create a new InsufficientStockException.
     *
     * @param  string  $variantId   UUID of the product variant that is short on stock.
     * @param  int     $requested   Number of units that were requested.
     * @param  int     $available   Number of units actually available at the time of the check.
     */
    public function __construct(
        public readonly string $variantId,
        public readonly int    $requested,
        public readonly int    $available,
    ) {
        parent::__construct(
            "Insufficient stock for variant [{$variantId}]: requested {$requested}, available {$available}."
        );
    }
}
