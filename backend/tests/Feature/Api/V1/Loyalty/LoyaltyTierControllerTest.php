<?php

declare(strict_types=1);

use App\Domain\Loyalty\Models\LoyaltyTier;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('LoyaltyTierController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists loyalty tiers', function () {
        LoyaltyTier::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/loyalty/tiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'min_lifetime_points', 'discount_percent'],
                ],
            ]);

        expect(count($response->json('data')))->toBeGreaterThanOrEqual(3);
    });

    it('creates a loyalty tier', function () {
        $response = $this->postJson('/api/v1/loyalty/tiers', [
            'name'                => 'Thành Viên Vàng',
            'slug'                => 'thanh-vien-vang',
            'min_lifetime_points' => 5000,
            'discount_percent'    => 5.00,
            'free_shipping'       => true,
            'early_access'        => false,
            'birthday_multiplier' => 2.00,
            'perks'               => ['free_shipping', 'priority_support'],
            'color'               => '#FFD700',
            'sort_order'          => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Thành Viên Vàng')
            ->assertJsonPath('data.slug', 'thanh-vien-vang');

        $this->assertDatabaseHas('loyalty_tiers', [
            'name' => 'Thành Viên Vàng',
            'slug' => 'thanh-vien-vang',
        ]);
    });

    it('updates a loyalty tier', function () {
        $tier = LoyaltyTier::factory()->create([
            'name'                => 'Hạng Bạc',
            'slug'                => 'hang-bac',
            'min_lifetime_points' => 1000,
            'discount_percent'    => 2.00,
        ]);

        $response = $this->putJson("/api/v1/loyalty/tiers/{$tier->id}", [
            'discount_percent'    => 3.50,
            'min_lifetime_points' => 1500,
            'free_shipping'       => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('loyalty_tiers', [
            'id'               => $tier->id,
            'min_lifetime_points' => 1500,
        ]);
    });

    it('shows a single tier', function () {
        $tier = LoyaltyTier::factory()->create([
            'name' => 'Hạng Kim Cương',
            'slug' => 'hang-kim-cuong',
        ]);

        $response = $this->getJson("/api/v1/loyalty/tiers/{$tier->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $tier->id)
            ->assertJsonPath('data.name', 'Hạng Kim Cương');
    });

    it('deletes a loyalty tier', function () {
        $tier = LoyaltyTier::factory()->create([
            'name' => 'Hạng Xóa',
            'slug' => 'hang-xoa',
        ]);

        $response = $this->deleteJson("/api/v1/loyalty/tiers/{$tier->id}");

        $response->assertStatus(204);
    });
});
