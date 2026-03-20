<?php

declare(strict_types=1);

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Supplier;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('PurchaseOrderController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->supplier = Supplier::factory()->create([
            'code'      => 'SUP-001',
            'name'      => 'Nhà Cung Cấp Vải Nam',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HN-01',
            'name'      => 'Kho Hà Nội',
            'type'      => 'main',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-PO-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
        ]);
    });

    it('lists purchase orders', function () {
        PurchaseOrder::factory()->count(2)->create([
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by'   => $this->admin->id,
            'status'       => 'draft',
        ]);

        $response = $this->getJson('/api/v1/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'po_number', 'status'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(2);
    });

    it('creates a purchase order', function () {
        $response = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id'         => $this->supplier->id,
            'warehouse_id'        => $this->warehouse->id,
            'expected_arrival_at' => '2026-04-15',
            'notes'               => 'Đặt hàng vải mùa hè',
            'items'               => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_ordered'   => 100,
                    'unit_cost'          => 80000,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonStructure([
                'data' => ['id', 'po_number', 'supplier', 'warehouse', 'items'],
            ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'draft',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_variant_id' => $this->variant->id,
            'quantity_ordered'   => 100,
        ]);
    });

    it('approves a purchase order', function () {
        $po = PurchaseOrder::factory()->create([
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by'   => $this->admin->id,
            'status'       => 'draft',
        ]);

        $response = $this->postJson("/api/v1/purchase-orders/{$po->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('purchase_orders', [
            'id'          => $po->id,
            'status'      => 'confirmed',
            'approved_by' => $this->admin->id,
        ]);
    });

    it('shows a purchase order with items', function () {
        $po = PurchaseOrder::factory()->create([
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by'   => $this->admin->id,
            'status'       => 'draft',
        ]);

        $response = $this->getJson("/api/v1/purchase-orders/{$po->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $po->id)
            ->assertJsonStructure([
                'data' => ['id', 'po_number', 'status', 'supplier', 'warehouse'],
            ]);
    });
});
