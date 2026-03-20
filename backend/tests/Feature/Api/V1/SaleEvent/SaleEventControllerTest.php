<?php

declare(strict_types=1);

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\SaleEvent\Models\SaleEvent;
use App\Domain\SaleEvent\Models\SaleEventItem;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('SaleEventController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-SALE-001',
            'selling_price' => 299000,
            'compare_price' => 399000,
            'cost_price'    => 120000,
            'sale_price'    => null,
            'status'        => 'active',
        ]);
    });

    it('lists sale events', function () {
        SaleEvent::factory()->count(3)->create([
            'status'     => 'scheduled',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/sale-events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'type', 'status'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates a sale event', function () {
        $response = $this->postJson('/api/v1/sale-events', [
            'name'       => 'Flash Sale Tết 2026',
            'slug'       => 'flash-sale-tet-2026',
            'type'       => 'flash_sale',
            'status'     => 'scheduled',
            'starts_at'  => '2026-01-28 00:00:00',
            'ends_at'    => '2026-01-30 23:59:59',
            'is_public'  => true,
            'items'      => [
                [
                    'product_variant_id' => $this->variant->id,
                    'sale_price'         => 199000,
                    'compare_price'      => 299000,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Flash Sale Tết 2026')
            ->assertJsonPath('data.type', 'flash_sale')
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseHas('sale_events', [
            'name'   => 'Flash Sale Tết 2026',
            'slug'   => 'flash-sale-tet-2026',
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('sale_event_items', [
            'product_variant_id' => $this->variant->id,
            'sale_price'         => 199000,
        ]);
    });

    it('activates a sale event and updates variant prices', function () {
        $saleEvent = SaleEvent::factory()->create([
            'name'       => 'Drop Launch Hè 2026',
            'slug'       => 'drop-launch-he-2026',
            'type'       => 'drop_launch',
            'status'     => 'scheduled',
            'starts_at'  => now()->subHour(),
            'created_by' => $this->admin->id,
        ]);

        SaleEventItem::create([
            'sale_event_id'      => $saleEvent->id,
            'product_variant_id' => $this->variant->id,
            'sale_price'         => 199000,
        ]);

        $response = $this->postJson("/api/v1/sale-events/{$saleEvent->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('sale_events', [
            'id'     => $saleEvent->id,
            'status' => 'active',
        ]);

        // Variant sale price should be updated
        $this->assertDatabaseHas('product_variants', [
            'id'         => $this->variant->id,
            'sale_price' => 199000,
        ]);
    });

    it('ends a sale event and clears variant prices', function () {
        $saleEvent = SaleEvent::factory()->create([
            'name'       => 'Clearance Đang Chạy',
            'slug'       => 'clearance-dang-chay',
            'type'       => 'clearance',
            'status'     => 'active',
            'starts_at'  => now()->subDay(),
            'created_by' => $this->admin->id,
        ]);

        // Set sale price on variant
        $this->variant->update([
            'sale_price'    => 149000,
            'sale_event_id' => $saleEvent->id,
        ]);

        SaleEventItem::create([
            'sale_event_id'      => $saleEvent->id,
            'product_variant_id' => $this->variant->id,
            'sale_price'         => 149000,
        ]);

        $response = $this->postJson("/api/v1/sale-events/{$saleEvent->id}/end");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('sale_events', [
            'id'     => $saleEvent->id,
            'status' => 'ended',
        ]);

        // Variant sale price should be cleared
        $this->assertDatabaseHas('product_variants', [
            'id'         => $this->variant->id,
            'sale_price' => null,
        ]);
    });

    it('returns 422 when creating with duplicate slug', function () {
        SaleEvent::factory()->create([
            'slug'       => 'existing-slug',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->postJson('/api/v1/sale-events', [
            'name'      => 'Another Event',
            'slug'      => 'existing-slug',
            'type'      => 'flash_sale',
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });
});
