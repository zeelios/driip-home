<?php
declare(strict_types=1);
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('debug inventory transaction create', function () {
    Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);
    
    $product = Product::factory()->create(['status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'DRP-001', 'selling_price' => 200000]);
    $warehouse = Warehouse::factory()->create(['code' => 'WH-01', 'type' => 'main']);
    
    // Try to create a transaction
    try {
        $tx = InventoryTransaction::create([
            'product_variant_id' => $variant->id,
            'warehouse_id'       => $warehouse->id,
            'type'               => 'adjustment',
            'quantity_delta'     => 10,
            'quantity_before'    => 100,
            'quantity_after'     => 110,
            'reason'             => 'Manual adjustment',
            'created_by'         => $admin->id,
        ]);
        dump(['created' => $tx->toArray()]);
    } catch(\Exception $e) {
        dump(['error' => $e->getMessage()]);
    }
    expect(true)->toBeTrue();
});
