<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\StockTransfer;

/**
 * Action: approve a stock transfer, transitioning its status to 'approved'.
 *
 * Sets the approved_by field to the authorizing staff member.
 * Only transfers in 'draft' status should be approved.
 */
class ApproveStockTransferAction
{
    /**
     * Execute the stock transfer approval.
     *
     * @param  StockTransfer  $stockTransfer  The transfer to approve.
     * @param  string         $approvedBy     UUID of the staff user approving the transfer.
     *
     * @return StockTransfer  The updated stock transfer.
     */
    public function execute(StockTransfer $stockTransfer, string $approvedBy): StockTransfer
    {
        $stockTransfer->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
        ]);

        return $stockTransfer->fresh(['items', 'fromWarehouse', 'toWarehouse']);
    }
}
