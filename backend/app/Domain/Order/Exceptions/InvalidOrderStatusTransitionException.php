<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use RuntimeException;

/**
 * Thrown when an order status transition is not permitted by the state machine.
 *
 * Each order status has a fixed set of valid next statuses defined in
 * OrderStatusMachineService. This exception is raised when a caller
 * attempts a transition that falls outside those allowed paths.
 */
class InvalidOrderStatusTransitionException extends RuntimeException
{
    /**
     * Create a new exception for an invalid status transition.
     *
     * @param  string  $from  The current order status.
     * @param  string  $to    The requested target status.
     */
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Cannot transition order from [{$from}] to [{$to}]."
        );
    }
}
