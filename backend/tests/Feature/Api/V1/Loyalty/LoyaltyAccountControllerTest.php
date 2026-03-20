<?php

declare(strict_types=1);

use App\Domain\Customer\Models\Customer;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('LoyaltyAccountController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        // Create a loyalty tier first (required by FK constraint)
        $this->tier = LoyaltyTier::factory()->create([
            'name'               => 'Hạng Đồng',
            'slug'               => 'hang-dong',
            'min_lifetime_points' => 0,
        ]);

        $this->customer = Customer::factory()->create([
            'first_name' => 'Đặng',
            'last_name'  => 'Thị Lan',
        ]);

        $this->account = LoyaltyAccount::create([
            'customer_id'       => $this->customer->id,
            'tier_id'           => $this->tier->id,
            'points_balance'    => 1200,
            'lifetime_points'   => 3000,
            'lifetime_spending' => 12000000,
        ]);
    });

    it('shows customer loyalty account', function () {
        $response = $this->getJson("/api/v1/loyalty/accounts/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_id', $this->customer->id)
            ->assertJsonPath('data.points_balance', 1200)
            ->assertJsonStructure([
                'data' => ['id', 'customer_id', 'points_balance', 'lifetime_points'],
            ]);
    });

    it('earns points for customer', function () {
        $response = $this->postJson("/api/v1/loyalty/accounts/{$this->customer->id}/earn", [
            'points'         => 300,
            'reference_type' => 'order',
            'reference_id'   => '00000000-0000-0000-0000-000000000001',
            'description'    => 'Tích điểm từ đơn hàng',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'transaction_id']);

        // Balance should increase from 1200 to 1500
        $this->assertDatabaseHas('loyalty_accounts', [
            'id'             => $this->account->id,
            'points_balance' => 1500,
        ]);
    });

    it('redeems points for customer', function () {
        $response = $this->postJson("/api/v1/loyalty/accounts/{$this->customer->id}/redeem", [
            'points'      => 500,
            'description' => 'Đổi điểm cho đơn hàng mới',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'transaction_id']);

        // Balance should decrease from 1200 to 700
        $this->assertDatabaseHas('loyalty_accounts', [
            'id'             => $this->account->id,
            'points_balance' => 700,
        ]);
    });

    it('returns error when insufficient points to redeem', function () {
        $response = $this->postJson("/api/v1/loyalty/accounts/{$this->customer->id}/redeem", [
            'points'      => 9999,
            'description' => 'Vượt quá số điểm hiện có',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false)
            ->assertJsonStructure(['data' => ['request_code', 'message']]);
    });

    it('returns 404 when customer has no loyalty account', function () {
        $customerWithoutAccount = Customer::factory()->create();

        $response = $this->getJson("/api/v1/loyalty/accounts/{$customerWithoutAccount->id}");

        $response->assertStatus(404)
            ->assertJsonPath('data.success', false);
    });

    it('returns 422 when earn points amount is zero', function () {
        $response = $this->postJson("/api/v1/loyalty/accounts/{$this->customer->id}/earn", [
            'points' => 0,
        ]);

        $response->assertStatus(422);
    });
});
