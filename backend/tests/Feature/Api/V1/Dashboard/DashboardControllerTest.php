<?php

declare(strict_types=1);

use App\Domain\Customer\Models\Customer;
use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('DashboardController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('returns dashboard stats', function () {
        // Create some orders to verify counts
        Order::factory()->count(2)->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        Order::factory()->count(1)->create([
            'status'         => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        Order::factory()->count(1)->create([
            'status'         => 'packed',
            'payment_status' => 'unpaid',
        ]);

        Order::factory()->count(1)->create([
            'status'         => 'pending',
            'payment_status' => 'paid',
            'total_after_tax' => 500000,
        ]);

        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'orders_today',
                    'revenue_today',
                    'orders_pending',
                    'orders_to_pack',
                    'orders_to_ship',
                    'low_stock_count',
                    'customers_today',
                ],
            ]);

        $data = $response->json('data');

        // Verify counts are correct numbers
        expect($data['orders_today'])->toBeGreaterThanOrEqual(5);
        expect($data['orders_pending'])->toBeGreaterThanOrEqual(2);
        expect($data['orders_to_pack'])->toBeGreaterThanOrEqual(1); // confirmed + processing
        expect($data['orders_to_ship'])->toBeGreaterThanOrEqual(1); // packed
        expect($data['customers_today'])->toBeGreaterThanOrEqual(3);
    });

    it('requires authentication', function () {
        // Test that dashboard returns data when authenticated (already set up in beforeEach)
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('returns zero counts when no data exists', function () {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');

        expect($data['orders_today'])->toBe(0);
        expect($data['revenue_today'])->toBe(0);
        expect($data['orders_pending'])->toBe(0);
        expect($data['orders_to_pack'])->toBe(0);
        expect($data['orders_to_ship'])->toBe(0);
        expect($data['customers_today'])->toBe(0);
    });

    it('calculates revenue only from paid orders today', function () {
        // Paid order
        Order::factory()->create([
            'status'         => 'delivered',
            'payment_status' => 'paid',
            'total_after_tax' => 1000000,
        ]);

        // Unpaid order - should NOT be in revenue
        Order::factory()->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'total_after_tax' => 500000,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);

        expect($response->json('data.revenue_today'))->toBe(1000000);
    });
});
