<?php

declare(strict_types=1);

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('StockTransferController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->warehouseA = Warehouse::factory()->create([
            'code'      => 'WH-HN-01',
            'name'      => 'Kho Hà Nội',
            'type'      => 'main',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::factory()->create([
            'code'      => 'WH-HCM-01',
            'name'      => 'Kho TP. HCM',
            'type'      => 'satellite',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-TRANSFER-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
        ]);

        // Pre-populate inventory in source warehouse
        $this->inventory = Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouseA->id,
            'quantity_on_hand'   => 100,
            'quantity_reserved'  => 0,
            'quantity_available' => 100,
            'quantity_incoming'  => 0,
            'updated_at'         => now(),
        ]);
    });

    it('creates a stock transfer', function () {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id'   => $this->warehouseB->id,
            'reason'            => 'Điều phối hàng giữa các kho',
            'items'             => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_requested' => 20,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonStructure([
                'data' => ['id', 'transfer_number', 'status', 'from_warehouse', 'to_warehouse', 'items'],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id'   => $this->warehouseB->id,
            'status'            => 'draft',
        ]);
    });

    it('approves a stock transfer', function () {
        $transfer = StockTransfer::create([
            'transfer_number'   => 'TRF-TEST001',
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id'   => $this->warehouseB->id,
            'status'            => 'draft',
            'requested_by'      => $this->admin->id,
        ]);

        $response = $this->postJson("/api/v1/stock-transfers/{$transfer->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('stock_transfers', [
            'id'     => $transfer->id,
            'status' => 'approved',
        ]);
    });

    it('dispatches a stock transfer', function () {
        $transfer = StockTransfer::create([
            'transfer_number'   => 'TRF-TEST002',
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id'   => $this->warehouseB->id,
            'status'            => 'approved',
            'requested_by'      => $this->admin->id,
            'approved_by'       => $this->admin->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id'   => $transfer->id,
            'product_variant_id'  => $this->variant->id,
            'quantity_requested'  => 20,
            'quantity_dispatched' => 0,
            'quantity_received'   => 0,
        ]);

        $response = $this->postJson("/api/v1/stock-transfers/{$transfer->id}/dispatch");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'dispatched');

        // Source warehouse stock should decrease by 20
        $this->assertDatabaseHas('inventory', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouseA->id,
            'quantity_on_hand'   => 80, // 100 - 20
        ]);
    });

    it('returns 422 when from and to warehouses are the same', function () {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id'   => $this->warehouseA->id, // same
            'items'             => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_requested' => 10,
                ],
            ],
        ]);

        $response->assertStatus(422);
    });
});
