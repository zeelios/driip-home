<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use RuntimeException;

/**
 * Thrown when a cancellation is attempted on an order that cannot be cancelled.
 *
 * Once an order has progressed beyond the processing stage it is no longer
 * eligible for cancellation. Callers should check Order::isCancellable()
 * or catch this exception to handle the failure gracefully.
 */
class OrderNotCancellableException extends RuntimeException
{
    /**
     * Create a new exception for an order that cannot be cancelled.
     *
     * @param  string  $orderNumber  The human-readable order number.
     * @param  string  $status       The order's current status.
     */
    public function __construct(string $orderNumber, string $status)
    {
        parent::__construct(
            "Order [{$orderNumber}] cannot be cancelled in status [{$status}]."
        );
    }
}
