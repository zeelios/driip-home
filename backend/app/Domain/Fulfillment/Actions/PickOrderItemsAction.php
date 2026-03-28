<?php

declare(strict_types=1);

namespace App\Domain\Fulfillment\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Action to mark order items as picked by warehouse staff.
 *
 * Updates item status, timestamps, and optionally links inventory records.
 * Validates that items are in 'pending' status before picking.
 */
class PickOrderItemsAction
{
    /**
     * Execute the pick action on selected order items.
     *
     * @param  list<string>       $itemIds      Array of order item UUIDs
     * @param  string             $pickedBy     User ID performing the pick
     * @param  array<string,null> $inventoryIds Optional inventory ID per item
     * @return array<string,mixed>              Result with picked items count and items
     */
    public function execute(array $itemIds, string $pickedBy, array $inventoryIds = []): array
    {
        return DB::transaction(function () use ($itemIds, $pickedBy, $inventoryIds) {
            $items = OrderItem::whereIn('id', $itemIds)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            $pickedItems = [];

            foreach ($items as $item) {
                // Validate inventory availability if specified
                $inventoryId = $inventoryIds[$item->id] ?? null;

                if ($inventoryId !== null) {
                    $inventory = Inventory::find($inventoryId);

                    if (!$inventory || $inventory->quantity_available < 1) {
                        throw new \RuntimeException(
                            "Insufficient inventory for item {$item->sku} in selected location"
                        );
                    }

                    // Reserve the inventory
                    $inventory->decrement('quantity_available', 1);
                    $inventory->increment('quantity_reserved', 1);
                }

                $item->update([
                    'status' => 'picked',
                    'picked_at' => now(),
                    'picked_by' => $pickedBy,
                    'inventory_id' => $inventoryId,
                ]);

                $pickedItems[] = $item->fresh(['order', 'product', 'sizeOption', 'inventory']);
            }

            return [
                'picked_count' => count($pickedItems),
                'items' => $pickedItems,
            ];
        });
    }
}
