<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Data\CreateStockTransferDto;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;

/**
 * Action: create a new stock transfer request with its line items.
 *
 * Generates a sequential transfer number (DRP-ST-00001), creates the StockTransfer
 * header and each StockTransferItem within a single database transaction.
 */
class CreateStockTransferAction
{
    use GeneratesCode;

    /**
     * Execute the stock transfer creation.
     *
     * @param  CreateStockTransferDto  $dto  Validated data for the new stock transfer.
     *
     * @return StockTransfer  The newly created transfer with items loaded.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(CreateStockTransferDto $dto): StockTransfer
    {
        return DB::transaction(function () use ($dto): StockTransfer {
            $sequence = StockTransfer::withTrashed()->count() + 1;

            /** @var StockTransfer $transfer */
            $transfer = StockTransfer::create([
                'transfer_number'    => $this->buildCode('DRP-ST', $sequence, 5),
                'from_warehouse_id'  => $dto->fromWarehouseId,
                'to_warehouse_id'    => $dto->toWarehouseId,
                'status'             => 'draft',
                'reason'             => $dto->reason,
                'requested_by'       => $dto->requestedBy,
                'notes'              => $dto->notes,
            ]);

            foreach ($dto->items as $itemData) {
                StockTransferItem::create([
                    'stock_transfer_id'   => $transfer->id,
                    'product_variant_id'  => $itemData['product_variant_id'],
                    'quantity_requested'  => $itemData['quantity_requested'],
                    'quantity_dispatched' => null,
                    'quantity_received'   => null,
                    'notes'               => $itemData['notes'] ?? null,
                ]);
            }

            return $transfer->load(['items', 'fromWarehouse', 'toWarehouse']);
        });
    }
}
