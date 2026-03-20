<?php

declare(strict_types=1);

use App\Domain\Customer\Models\Customer;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('OrderController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->customer = Customer::factory()->create([
            'first_name' => 'Nguyễn',
            'last_name'  => 'Văn Hùng',
            'phone'      => '0901000001',
        ]);

        $this->product = Product::factory()->create(['status' => 'active']);
        $this->variant = ProductVariant::factory()->create([
            'product_id'    => $this->product->id,
            'sku'           => 'DRP-TEST-001',
            'selling_price' => 200000,
            'compare_price' => 250000,
            'cost_price'    => 100000,
            'status'        => 'active',
        ]);
    });

    it('lists orders with pagination', function () {
        Order::factory()->count(3)->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'payment_status'],
                ],
                'meta' => ['total', 'current_page'],
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates an order for a customer', function () {
        $response = $this->postJson('/api/v1/orders', [
            'customer_id'       => $this->customer->id,
            'shipping_name'     => 'Nguyễn Văn Hùng',
            'shipping_phone'    => '0901000001',
            'shipping_province' => 'Hà Nội',
            'shipping_district' => 'Đống Đa',
            'shipping_ward'     => 'Láng Thượng',
            'shipping_address'  => '123 Đường Láng, Phường Láng Thượng',
            'items'             => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity'           => 2,
                    'unit_price'         => 200000,
                ],
            ],
            'payment_method' => 'cod',
            'source'         => 'admin',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'order_number', 'status', 'customer'],
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id'    => $this->customer->id,
            'status'         => 'pending',
        ]);
    });

    it('creates a guest order', function () {
        $response = $this->postJson('/api/v1/orders', [
            'guest_name'        => 'Khách Vãng Lai',
            'guest_phone'       => '0912999888',
            'guest_email'       => 'khach@example.com',
            'shipping_name'     => 'Khách Vãng Lai',
            'shipping_phone'    => '0912999888',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'shipping_district' => 'Quận 1',
            'shipping_address'  => '456 Đường Lê Lai, Q.1',
            'items'             => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity'           => 1,
                    'unit_price'         => 200000,
                ],
            ],
            'payment_method' => 'bank_transfer',
            'source'         => 'web',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'guest_name'  => 'Khách Vãng Lai',
            'guest_phone' => '0912999888',
            'status'      => 'pending',
        ]);
    });

    it('confirms an order', function () {
        $order = Order::factory()->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    });

    it('packs an order', function () {
        $order = Order::factory()->create([
            'status'         => 'processing',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/pack");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'packed');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'packed',
        ]);
    });

    it('cancels an order', function () {
        $order = Order::factory()->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Khách hàng đổi ý, không muốn mua nữa',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'cancelled',
        ]);
    });

    it('returns order timeline/status history', function () {
        $order = Order::factory()->create([
            'status'         => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'from_status' => null,
            'to_status'   => 'pending',
            'note'        => 'Đơn hàng được tạo',
            'changed_by'  => $this->admin->id,
        ]);

        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'from_status' => 'pending',
            'to_status'   => 'confirmed',
            'note'        => 'Xác nhận đơn hàng',
            'changed_by'  => $this->admin->id,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'from_status', 'to_status'],
                ],
            ]);

        expect(count($response->json('data')))->toBe(2);
    });

    it('shows order with items', function () {
        $order = Order::factory()->create([
            'customer_id'    => $this->customer->id,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        \App\Domain\Order\Models\OrderItem::create([
            'order_id'           => $order->id,
            'product_variant_id' => $this->variant->id,
            'sku'                => $this->variant->sku,
            'name'               => 'Áo Thun Test',
            'quantity'           => 2,
            'unit_price'         => 200000,
            'total_price'        => 400000,
            'cost_price'         => 100000,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonStructure([
                'data' => ['id', 'order_number', 'status', 'items'],
            ]);

        expect(count($response->json('data.items')))->toBe(1);
    });

    it('returns 422 when items are missing from order creation', function () {
        $response = $this->postJson('/api/v1/orders', [
            'customer_id'       => $this->customer->id,
            'shipping_name'     => 'Test',
            'shipping_phone'    => '0900000000',
            'shipping_province' => 'Hà Nội',
            'shipping_address'  => '123 Test',
            // missing 'items'
        ]);

        $response->assertStatus(422);
    });
});
