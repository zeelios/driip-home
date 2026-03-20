<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\Exceptions\InvalidOrderStatusTransitionException;

/**
 * Manages valid order status transitions.
 *
 * Only allows transitions that are defined in the transition map.
 * Every status change must go through this service to ensure
 * business rules are enforced consistently.
 */
class OrderStatusMachineService
{
    /**
     * Valid transitions: current_status → list of allowed next statuses.
     *
     * @var array<string,list<string>>
     */
    private const TRANSITIONS = [
        'pending'            => ['confirmed', 'cancelled'],
        'confirmed'          => ['processing', 'cancelled'],
        'processing'         => ['packed', 'cancelled'],
        'packed'             => ['handed_to_courier', 'cancelled'],
        'handed_to_courier'  => ['shipped'],
        'shipped'            => ['in_transit'],
        'in_transit'         => ['out_for_delivery', 'failed_delivery'],
        'out_for_delivery'   => ['delivered', 'failed_delivery'],
        'delivered'          => ['returned', 'disputed'],
        'failed_delivery'    => ['returning', 'on_hold'],
        'returning'          => ['returned'],
        'returned'           => ['return_processing'],
        'return_processing'  => ['return_completed', 'refunded'],
        'on_hold'            => ['confirmed', 'cancelled'],
        'disputed'           => ['resolved', 'refunded'],
    ];

    /**
     * Assert that a status transition is valid.
     *
     * @param  string  $from  The current status of the order.
     * @param  string  $to    The desired target status.
     *
     * @throws InvalidOrderStatusTransitionException  If the transition is not allowed.
     */
    public function assertValidTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (!in_array($to, $allowed, true)) {
            throw new InvalidOrderStatusTransitionException($from, $to);
        }
    }

    /**
     * Get all allowed next statuses from a given status.
     *
     * @param  string  $from  The current order status.
     * @return list<string>   List of statuses this order may transition into.
     */
    public function allowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }
}
