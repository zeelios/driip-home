<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Action: receive stock into a warehouse for a specific product variant.
 *
 * Increments quantity_on_hand and quantity_available, decrements quantity_incoming
 * if applicable, and creates a 'receive' InventoryTransaction for auditability.
 * All changes are wrapped in a database transaction with a row-level lock.
 */
class ReceiveStockAction
{
    /**
     * Execute the stock receive operation.
     *
     * @param  string      $variantId     UUID of the product variant being received.
     * @param  string      $warehouseId   UUID of the destination warehouse.
     * @param  int         $quantity      Number of units to add to on-hand stock.
     * @param  string      $referenceType The type of source reference (e.g. 'purchase_order').
     * @param  string      $referenceId   UUID of the referencing entity.
     * @param  int|null    $unitCost      Unit cost in VND (optional).
     * @param  string|null $createdBy     UUID of the staff user executing the operation.
     * @param  string|null $notes         Optional notes for the transaction.
     *
     * @return InventoryTransaction The recorded receive transaction.
     */
    public function execute(
        string  $variantId,
        string  $warehouseId,
        int     $quantity,
        string  $referenceType,
        string  $referenceId,
        ?int    $unitCost = null,
        ?string $createdBy = null,
        ?string $notes = null,
    ): InventoryTransaction {
        return DB::transaction(function () use ($variantId, $warehouseId, $quantity, $referenceType, $referenceId, $unitCost, $createdBy, $notes) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['product_variant_id' => $variantId, 'warehouse_id' => $warehouseId],
                    [
                        'quantity_on_hand'   => 0,
                        'quantity_reserved'  => 0,
                        'quantity_available' => 0,
                        'quantity_incoming'  => 0,
                        'updated_at'         => now(),
                    ]
                );

            $before = $inventory->quantity_on_hand;

            $inventory->quantity_on_hand   += $quantity;
            $inventory->quantity_incoming   = max(0, $inventory->quantity_incoming - $quantity);
            $inventory->quantity_available  = $inventory->quantity_on_hand - $inventory->quantity_reserved;
            $inventory->updated_at          = now();
            $inventory->save();

            /** @var InventoryTransaction $transaction */
            $transaction = InventoryTransaction::create([
                'product_variant_id' => $variantId,
                'warehouse_id'       => $warehouseId,
                'type'               => 'receive',
                'quantity'           => $quantity,
                'quantity_before'    => $before,
                'quantity_after'     => $inventory->quantity_on_hand,
                'unit_cost'          => $unitCost,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'notes'              => $notes,
                'created_by'         => $createdBy,
                'created_at'         => now(),
            ]);

            return $transaction;
        });
    }
}
