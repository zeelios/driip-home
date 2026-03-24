<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\Exceptions\InvalidOrderStatusTransitionException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Staff\Models\User;

/**
 * Manages valid order status transitions.
 *
 * Only allows transitions that are defined in the transition map.
 * Every status change must go through this service to ensure
 * business rules are enforced consistently.
 */
class OrderStatusMachineService
{
    public function __construct(
        private readonly OrderActivityLogger $activityLogger
    ) {
    }

    /**
     * Valid transitions: current_status → list of allowed next statuses.
     *
     * @var array<string,list<string>>
     */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['handed_to_courier', 'cancelled'],
        'handed_to_courier' => ['shipped'],
        'shipped' => ['in_transit'],
        'in_transit' => ['out_for_delivery', 'failed_delivery'],
        'out_for_delivery' => ['delivered', 'failed_delivery'],
        'delivered' => ['returned', 'disputed'],
        'failed_delivery' => ['returning', 'on_hold'],
        'returning' => ['returned'],
        'returned' => ['return_processing'],
        'return_processing' => ['return_completed', 'refunded'],
        'on_hold' => ['confirmed', 'cancelled'],
        'disputed' => ['resolved', 'refunded'],
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

    /**
     * Determine whether a transition from one status to another is valid.
     *
     * Unlike assertValidTransition, this method does not throw; it simply
     * returns a boolean so callers can branch on the result.
     *
     * @param  string  $from  The current order status.
     * @param  string  $to    The desired target status.
     * @return bool           True when the transition is in the allowed map.
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Transition an order to a new status, persist the history record,
     * and update any relevant timestamp columns.
     *
     * @param  Order        $order      The order to transition.
     * @param  string       $newStatus  The target status.
     * @param  string|null  $notes      Optional human-readable notes for the history entry.
     * @param  User|null    $by         The staff member performing the transition, or null for system.
     * @return OrderStatusHistory       The created history record.
     *
     * @throws InvalidOrderStatusTransitionException  If the transition is not allowed.
     */
    public function transition(Order $order, string $newStatus, ?string $notes, ?User $by): OrderStatusHistory
    {
        $this->assertValidTransition($order->status, $newStatus);

        $fromStatus = $order->status;

        $timestamps = match ($newStatus) {
            'confirmed' => ['confirmed_at' => now()],
            'packed' => ['packed_at' => now()],
            'delivered' => ['delivered_at' => now()],
            'cancelled' => ['cancelled_at' => now()],
            default => [],
        };

        $order->update(array_merge(['status' => $newStatus], $timestamps));

        /** @var OrderStatusHistory $history */
        $history = OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $newStatus,
            'note' => $notes,
            'created_by' => $by?->id,
            'created_at' => now(),
        ]);

        // Log to activity log
        $this->activityLogger->logStatusChange($order, $fromStatus, $newStatus, $notes, $by);

        return $history;
    }
}
