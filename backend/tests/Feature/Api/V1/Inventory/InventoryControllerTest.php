<?php

declare(strict_types=1);

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('InventoryController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-INV-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
            'status'        => 'active',
        ]);
        $this->warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HCM-01',
            'name'      => 'Kho TP. HCM',
            'type'      => 'main',
            'is_active' => true,
        ]);
        $this->inventory = Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'quantity_on_hand'   => 100,
            'quantity_reserved'  => 10,
            'quantity_available' => 90,
            'quantity_incoming'  => 20,
            'updated_at'         => now(),
        ]);
    });

    it('lists inventory', function () {
        $response = $this->getJson('/api/v1/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'quantity_on_hand', 'quantity_reserved'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
    });

    it('shows inventory for a specific variant', function () {
        $response = $this->getJson("/api/v1/inventory/{$this->variant->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'quantity_on_hand', 'quantity_reserved'],
                ],
            ]);

        $variantIds = collect($response->json('data'))->pluck('product_variant.id');
        $variantIds->each(fn ($id) => expect($id)->toBe($this->variant->id));
    });

    it('adjusts inventory quantity', function () {
        $response = $this->postJson('/api/v1/inventory/adjust', [
            'variant_id'   => $this->variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 25,
            'reason'       => 'Nhập hàng bổ sung từ nhà cung cấp',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'type', 'quantity'],
            ]);

        // On-hand should now be 100 + 25 = 125
        $this->assertDatabaseHas('inventory', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'quantity_on_hand'   => 125,
        ]);
    });

    it('lists inventory movements/transactions', function () {
        InventoryTransaction::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'type'               => 'adjustment',
            'quantity'           => 10,
            'quantity_before'    => 100,
            'quantity_after'     => 110,
            'reason'             => 'Manual adjustment',
            'created_by'         => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/inventory/movements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'quantity'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
    });

    it('returns 422 when adjusting with zero quantity', function () {
        $response = $this->postJson('/api/v1/inventory/adjust', [
            'variant_id'   => $this->variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 0,
            'reason'       => 'Zero should fail',
        ]);

        $response->assertStatus(422);
    });

    it('filters inventory by warehouse', function () {
        $anotherWarehouse = Warehouse::factory()->create([
            'code' => 'WH-HN-02',
            'name' => 'Kho Hà Nội 2',
            'type' => 'satellite',
        ]);

        Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $anotherWarehouse->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 0,
            'quantity_available' => 50,
            'quantity_incoming'  => 0,
            'updated_at'         => now(),
        ]);

        $response = $this->getJson("/api/v1/inventory?filter[warehouse_id]={$this->warehouse->id}");

        $response->assertStatus(200);

        $warehouseIds = collect($response->json('data'))->pluck('warehouse.id');
        $warehouseIds->each(fn ($id) => expect($id)->toBe($this->warehouse->id));
    });
});
