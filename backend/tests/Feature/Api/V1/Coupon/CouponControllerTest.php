<?php

declare(strict_types=1);

use App\Domain\Coupon\Models\Coupon;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CouponController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists coupons', function () {
        Coupon::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/coupons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'type', 'is_active'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates a coupon', function () {
        $response = $this->postJson('/api/v1/coupons', [
            'code'       => 'DRIIP2026',
            'name'       => 'Giảm 10% toàn bộ đơn hàng',
            'type'       => 'percent',
            'value'      => 10,
            'is_active'  => true,
            'is_public'  => true,
            'starts_at'  => '2026-01-01',
            'expires_at' => '2026-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'DRIIP2026')
            ->assertJsonPath('data.type', 'percent');

        $this->assertDatabaseHas('coupons', [
            'code'      => 'DRIIP2026',
            'is_active' => true,
        ]);
    });

    it('returns 422 when creating coupon with duplicate code', function () {
        Coupon::factory()->create(['code' => 'EXISTING']);

        $response = $this->postJson('/api/v1/coupons', [
            'code'  => 'EXISTING',
            'name'  => 'Duplicate Coupon',
            'type'  => 'fixed_amount',
            'value' => 50000,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    });

    it('validates a coupon code returns discount amount', function () {
        Coupon::factory()->create([
            'code'             => 'SUMMER2026',
            'type'             => 'fixed_amount',
            'value'            => 100000,
            'is_active'        => true,
            'min_order_amount' => 300000,
            'starts_at'        => now()->subDay(),
            'expires_at'       => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code'         => 'SUMMER2026',
            'order_amount' => 500000,
            'item_count'   => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount_amount', 100000);
    });

    it('returns error when coupon is expired', function () {
        Coupon::factory()->create([
            'code'       => 'EXPIRED2025',
            'type'       => 'percent',
            'value'      => 15,
            'is_active'  => true,
            'starts_at'  => '2025-01-01',
            'expires_at' => '2025-12-31',
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code'         => 'EXPIRED2025',
            'order_amount' => 500000,
            'item_count'   => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', false);
    });

    it('returns error when coupon is inactive', function () {
        Coupon::factory()->create([
            'code'      => 'INACTIVE',
            'type'      => 'percent',
            'value'     => 20,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code'         => 'INACTIVE',
            'order_amount' => 500000,
            'item_count'   => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', false);
    });

    it('returns error when coupon code does not exist', function () {
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code'         => 'NOTFOUND',
            'order_amount' => 500000,
            'item_count'   => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', false);
    });
});
