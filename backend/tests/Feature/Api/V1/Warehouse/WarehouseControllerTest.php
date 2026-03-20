<?php

declare(strict_types=1);

use App\Domain\Inventory\Models\Inventory;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('WarehouseController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists warehouses', function () {
        Warehouse::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/warehouses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'type'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates a warehouse', function () {
        $response = $this->postJson('/api/v1/warehouses', [
            'code'      => 'WH-DN-01',
            'name'      => 'Kho Đà Nẵng',
            'type'      => 'satellite',
            'address'   => '789 Đường Ngô Quyền',
            'province'  => 'Đà Nẵng',
            'district'  => 'Hải Châu',
            'phone'     => '0236123456',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Kho Đà Nẵng')
            ->assertJsonPath('data.code', 'WH-DN-01')
            ->assertJsonPath('data.type', 'satellite');

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-DN-01',
            'name' => 'Kho Đà Nẵng',
        ]);
    });

    it('shows warehouse with inventory', function () {
        $warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HN-02',
            'name'      => 'Kho Hà Nội 2',
            'type'      => 'main',
            'is_active' => true,
        ]);

        $product = Product::factory()->create(['status' => 'active']);
        $variant = ProductVariant::factory()->create([
            'product_id'    => $product->id,
            'sku'           => 'DRP-WH-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'warehouse_id'       => $warehouse->id,
            'quantity_on_hand'   => 75,
            'quantity_reserved'  => 5,
            'quantity_available' => 70,
            'quantity_incoming'  => 0,
            'updated_at'         => now(),
        ]);

        $response = $this->getJson("/api/v1/warehouses/{$warehouse->id}/inventory");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'quantity_on_hand', 'quantity_reserved'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(1);
    });

    it('assigns staff to warehouse', function () {
        $warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HCM-02',
            'name'      => 'Kho TP. HCM 2',
            'type'      => 'satellite',
            'is_active' => true,
        ]);

        $staff = User::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/v1/warehouses/{$warehouse->id}/staff", [
            'user_id' => $staff->id,
            'role'    => 'picker',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $staff->id)
            ->assertJsonPath('data.role', 'picker');

        $this->assertDatabaseHas('warehouse_staff', [
            'warehouse_id' => $warehouse->id,
            'user_id'      => $staff->id,
            'role'         => 'picker',
        ]);
    });

    it('returns 422 when creating warehouse with duplicate code', function () {
        Warehouse::factory()->create(['code' => 'WH-DUPE-01']);

        $response = $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-DUPE-01',
            'name' => 'Another Warehouse',
            'type' => 'virtual',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    });

    it('updates a warehouse', function () {
        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-UPDATE',
            'name' => 'Kho Cũ',
            'type' => 'main',
        ]);

        $response = $this->putJson("/api/v1/warehouses/{$warehouse->id}", [
            'name'      => 'Kho Mới',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Kho Mới');

        $this->assertDatabaseHas('warehouses', [
            'id'        => $warehouse->id,
            'name'      => 'Kho Mới',
            'is_active' => false,
        ]);
    });
});
