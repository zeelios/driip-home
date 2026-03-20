<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\PurchaseOrder;

/**
 * Action: approve a purchase order, transitioning its status to 'confirmed'.
 *
 * Sets the approved_by and approved_at fields and updates the status.
 * Only purchase orders in 'draft' or 'sent' status should be approved.
 */
class ApprovePurchaseOrderAction
{
    /**
     * Execute the purchase order approval.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order to approve.
     * @param  string         $approvedBy     UUID of the staff user approving the order.
     *
     * @return PurchaseOrder  The updated purchase order.
     */
    public function execute(PurchaseOrder $purchaseOrder, string $approvedBy): PurchaseOrder
    {
        $purchaseOrder->update([
            'status'      => 'confirmed',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $purchaseOrder->fresh(['supplier', 'warehouse', 'items']);
    }
}
