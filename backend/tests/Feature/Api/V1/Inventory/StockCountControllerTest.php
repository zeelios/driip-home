<?php

declare(strict_types=1);

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\StockCount;
use App\Domain\Inventory\Models\StockCountItem;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('StockCountController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HN-01',
            'name'      => 'Kho Hà Nội',
            'type'      => 'main',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-CNT-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
        ]);

        Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 0,
            'quantity_available' => 50,
            'quantity_incoming'  => 0,
            'updated_at'         => now(),
        ]);
    });

    it('creates a stock count', function () {
        $response = $this->postJson('/api/v1/stock-counts', [
            'warehouse_id' => $this->warehouse->id,
            'type'         => 'full',
            'scheduled_at' => '2026-04-01',
            'notes'        => 'Kiểm kê định kỳ quý 2/2026',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.type', 'full')
            ->assertJsonStructure([
                'data' => ['id', 'count_number', 'status', 'warehouse', 'items'],
            ]);

        $this->assertDatabaseHas('stock_counts', [
            'warehouse_id' => $this->warehouse->id,
            'type'         => 'full',
            'status'       => 'draft',
        ]);

        // Items should be auto-populated from warehouse inventory
        $this->assertDatabaseHas('stock_count_items', [
            'product_variant_id' => $this->variant->id,
            'quantity_expected'  => 50,
        ]);
    });

    it('records item count', function () {
        $stockCount = StockCount::create([
            'count_number' => 'CNT-TEST001',
            'warehouse_id' => $this->warehouse->id,
            'type'         => 'cycle_count',
            'status'       => 'draft',
            'created_by'   => $this->admin->id,
        ]);

        $item = StockCountItem::create([
            'stock_count_id'     => $stockCount->id,
            'product_variant_id' => $this->variant->id,
            'quantity_expected'  => 50,
        ]);

        $response = $this->postJson("/api/v1/stock-counts/{$stockCount->id}/items/{$item->id}/count", [
            'quantity_counted' => 48,
            'notes'            => 'Thiếu 2 sản phẩm',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity_counted', 48)
            ->assertJsonPath('data.variance', -2);

        $this->assertDatabaseHas('stock_count_items', [
            'id'               => $item->id,
            'quantity_counted' => 48,
            'variance'         => -2,
        ]);
    });

    it('completes a stock count', function () {
        $stockCount = StockCount::create([
            'count_number' => 'CNT-TEST002',
            'warehouse_id' => $this->warehouse->id,
            'type'         => 'spot_check',
            'status'       => 'draft',
            'created_by'   => $this->admin->id,
        ]);

        StockCountItem::create([
            'stock_count_id'     => $stockCount->id,
            'product_variant_id' => $this->variant->id,
            'quantity_expected'  => 50,
            'quantity_counted'   => 45,
            'variance'           => -5,
        ]);

        $response = $this->postJson("/api/v1/stock-counts/{$stockCount->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('stock_counts', [
            'id'     => $stockCount->id,
            'status' => 'completed',
        ]);
    });

    it('returns 422 when warehouse_id is missing', function () {
        $response = $this->postJson('/api/v1/stock-counts', [
            'type' => 'full',
        ]);

        $response->assertStatus(422);
    });

    it('returns 422 when type is invalid', function () {
        $response = $this->postJson('/api/v1/stock-counts', [
            'warehouse_id' => $this->warehouse->id,
            'type'         => 'invalid_type',
        ]);

        $response->assertStatus(422);
    });
});
