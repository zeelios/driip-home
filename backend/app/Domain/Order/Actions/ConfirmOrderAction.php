<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Order\Services\OrderStatusMachineService;
use App\Domain\Staff\Models\User;
use App\Jobs\SendNotificationJob;

/**
 * Action to confirm a pending order.
 *
 * Validates the pending → confirmed transition via the state machine,
 * updates the order record, records the status history entry, and
 * dispatches a customer notification.
 */
class ConfirmOrderAction
{
    /**
     * Create a new ConfirmOrderAction.
     *
     * @param  OrderStatusMachineService  $stateMachine  Validates allowed status transitions.
     */
    public function __construct(
        private readonly OrderStatusMachineService $stateMachine,
    ) {}

    /**
     * Execute the order confirmation.
     *
     * @param  Order  $order  The order to confirm (must currently be in 'pending' status).
     * @param  User   $actor  The staff member performing the confirmation.
     * @return Order          The updated order instance.
     *
     * @throws \App\Domain\Order\Exceptions\InvalidOrderStatusTransitionException
     */
    public function execute(Order $order, User $actor): Order
    {
        $this->stateMachine->assertValidTransition($order->status, 'confirmed');

        $order->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id'            => $order->id,
            'from_status'         => 'pending',
            'to_status'           => 'confirmed',
            'note'                => 'Order confirmed.',
            'is_customer_visible' => true,
            'created_by'          => $actor->id,
            'created_at'          => now(),
        ]);

        $this->dispatchConfirmationNotification($order);

        return $order->refresh();
    }

    /**
     * Dispatch the order-confirmed notification to the customer.
     *
     * Uses the customer email when a registered customer is linked,
     * falling back to the guest email for anonymous orders.
     *
     * @param  Order  $order
     */
    private function dispatchConfirmationNotification(Order $order): void
    {
        $order->loadMissing('customer');

        $email = $order->customer?->email ?? $order->guest_email;

        if ($email === null) {
            return;
        }

        SendNotificationJob::dispatch(
            'order_confirmed',
            $email,
            [
                'order_number'  => $order->order_number,
                'customer_name' => $order->customer?->fullName() ?? $order->guest_name ?? '',
                'total'         => number_format($order->total_after_tax),
            ],
            Order::class,
            $order->id
        );
    }
}
