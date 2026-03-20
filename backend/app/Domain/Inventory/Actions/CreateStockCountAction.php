<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Data\CreateStockCountDto;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\StockCount;
use App\Domain\Inventory\Models\StockCountItem;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;

/**
 * Action: create a new stock count task and populate items from current inventory.
 *
 * Generates a sequential count number (DRP-SC-00001), creates the StockCount header,
 * and populates StockCountItem rows with the current on-hand quantities from inventory
 * as the expected quantities. All operations run within a single transaction.
 */
class CreateStockCountAction
{
    use GeneratesCode;

    /**
     * Execute the stock count creation.
     *
     * @param  CreateStockCountDto  $dto  Validated data for the new stock count.
     *
     * @return StockCount  The newly created stock count with items loaded.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(CreateStockCountDto $dto): StockCount
    {
        return DB::transaction(function () use ($dto): StockCount {
            $sequence = StockCount::count() + 1;

            /** @var StockCount $stockCount */
            $stockCount = StockCount::create([
                'count_number' => $this->buildCode('DRP-SC', $sequence, 5),
                'warehouse_id' => $dto->warehouseId,
                'type'         => $dto->type,
                'status'       => 'draft',
                'scheduled_at' => $dto->scheduledAt,
                'notes'        => $dto->notes,
                'created_by'   => $dto->createdBy,
            ]);

            // Populate count items from current inventory in this warehouse.
            // If specific variant IDs are provided, only count those; otherwise count all.
            $inventoryQuery = Inventory::where('warehouse_id', $dto->warehouseId);

            if (!empty($dto->variantIds)) {
                $inventoryQuery->whereIn('product_variant_id', $dto->variantIds);
            }

            $inventoryRecords = $inventoryQuery->get();

            foreach ($inventoryRecords as $inventoryRecord) {
                StockCountItem::create([
                    'stock_count_id'     => $stockCount->id,
                    'product_variant_id' => $inventoryRecord->product_variant_id,
                    'quantity_expected'  => $inventoryRecord->quantity_on_hand,
                    'quantity_counted'   => null,
                    'variance'           => null,
                    'variance_value'     => null,
                ]);
            }

            return $stockCount->load(['items', 'warehouse']);
        });
    }
}
