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

describe('ProductVariantController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->product = Product::factory()->create([
            'name'   => 'Áo Thun Driip',
            'status' => 'active',
        ]);
    });

    it('lists variants for a product', function () {
        ProductVariant::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'status'     => 'active',
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->id}/variants");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'selling_price', 'status'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates a variant for a product', function () {
        $response = $this->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku'              => 'DRP-THUN-M-DEN',
            'attribute_values' => ['size' => 'M', 'color' => 'Đen'],
            'compare_price'    => 299000,
            'cost_price'       => 120000,
            'selling_price'    => 249000,
            'weight_grams'     => 200,
            'status'           => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.sku', 'DRP-THUN-M-DEN')
            ->assertJsonPath('data.selling_price', 249000);

        $this->assertDatabaseHas('product_variants', [
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-THUN-M-DEN',
            'selling_price' => 249000,
        ]);
    });

    it('updates a variant', function () {
        $variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-THUN-L-TRANG',
            'selling_price' => 249000,
            'compare_price' => 299000,
            'cost_price'    => 120000,
            'status'        => 'active',
        ]);

        $response = $this->putJson("/api/v1/products/{$this->product->id}/variants/{$variant->id}", [
            'selling_price' => 229000,
            'status'        => 'active',
            'reason'        => 'Giảm giá cuối vụ',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.selling_price', 229000);

        $this->assertDatabaseHas('product_variants', [
            'id'            => $variant->id,
            'selling_price' => 229000,
        ]);
    });

    it('shows variant inventory', function () {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'status'     => 'active',
        ]);

        $warehouse = Warehouse::factory()->create([
            'code'      => 'WH-HN-01',
            'name'      => 'Kho Hà Nội',
            'type'      => 'main',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'warehouse_id'       => $warehouse->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 5,
            'quantity_available' => 45,
            'quantity_incoming'  => 0,
            'updated_at'         => now(),
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->id}/variants/{$variant->id}/inventory");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_on_hand', 50)
            ->assertJsonPath('data.total_reserved', 5)
            ->assertJsonPath('data.total_available', 45)
            ->assertJsonStructure([
                'data' => ['variant_id', 'sku', 'total_on_hand', 'total_reserved', 'total_available', 'warehouses'],
            ]);
    });

    it('returns 422 when sku is missing', function () {
        $response = $this->postJson("/api/v1/products/{$this->product->id}/variants", [
            'attribute_values' => ['size' => 'S'],
            'compare_price'    => 299000,
            'cost_price'       => 120000,
            'selling_price'    => 249000,
        ]);

        $response->assertStatus(422);
    });
});
