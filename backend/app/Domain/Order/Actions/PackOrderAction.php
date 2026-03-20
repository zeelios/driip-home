<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Order\Services\OrderStatusMachineService;
use App\Domain\Staff\Models\User;

/**
 * Action to mark an order as packed and ready for courier handover.
 *
 * Validates that the order is in a state that allows packing,
 * records the packer identity and timestamp, and appends a status
 * history entry. Orders that are still in 'pending' are auto-confirmed
 * before packing to cover manual fulfilment workflows.
 */
class PackOrderAction
{
    /**
     * Create a new PackOrderAction.
     *
     * @param  OrderStatusMachineService  $stateMachine  Validates allowed status transitions.
     * @param  ConfirmOrderAction         $confirm       Used to auto-confirm pending orders.
     */
    public function __construct(
        private readonly OrderStatusMachineService $stateMachine,
        private readonly ConfirmOrderAction        $confirm,
    ) {}

    /**
     * Execute the pack operation.
     *
     * If the order is still pending, it will be confirmed automatically
     * before being moved to the packed state. The actor is recorded as
     * both the confirmer and the packer in that scenario.
     *
     * @param  Order  $order  The order to pack.
     * @param  User   $actor  The staff member performing the packing.
     * @return Order          The updated order instance.
     *
     * @throws \App\Domain\Order\Exceptions\InvalidOrderStatusTransitionException
     */
    public function execute(Order $order, User $actor): Order
    {
        if ($order->status === 'pending') {
            $this->confirm->execute($order, $actor);
            $order->refresh();
        }

        $this->stateMachine->assertValidTransition($order->status, 'packed');

        $fromStatus = $order->status;

        $order->update([
            'status'    => 'packed',
            'packed_by' => $actor->id,
            'packed_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id'            => $order->id,
            'from_status'         => $fromStatus,
            'to_status'           => 'packed',
            'note'                => 'Order packed and ready for courier.',
            'is_customer_visible' => false,
            'created_by'          => $actor->id,
            'created_at'          => now(),
        ]);

        return $order->refresh();
    }
}
