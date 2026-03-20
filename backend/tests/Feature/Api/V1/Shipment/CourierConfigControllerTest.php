<?php

declare(strict_types=1);

use App\Domain\Shipment\Models\CourierConfig;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CourierConfigController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists courier configs', function () {
        CourierConfig::factory()->create([
            'courier_code' => 'ghn',
            'name'         => 'Giao Hàng Nhanh',
            'is_active'    => true,
        ]);

        CourierConfig::factory()->create([
            'courier_code' => 'ghtk',
            'name'         => 'Giao Hàng Tiết Kiệm',
            'is_active'    => true,
        ]);

        $response = $this->getJson('/api/v1/courier-configs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'courier_code', 'name', 'is_active'],
                ],
            ]);

        expect(count($response->json('data')))->toBe(2);
    });

    it('creates a courier config', function () {
        $response = $this->postJson('/api/v1/courier-configs', [
            'courier_code'    => 'jt',
            'name'            => 'J&T Express',
            'api_endpoint'    => 'https://api.jtexpress.vn',
            'api_key'         => 'JT_SECRET_KEY_123',
            'account_id'      => 'DRIIP001',
            'pickup_hub_code' => 'HN01',
            'is_active'       => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.courier_code', 'jt')
            ->assertJsonPath('data.name', 'J&T Express');

        $this->assertDatabaseHas('courier_configs', [
            'courier_code' => 'jt',
            'name'         => 'J&T Express',
            'is_active'    => true,
        ]);

        // Sensitive credentials should NOT be visible in response
        expect($response->json('data.api_key'))->toBeNull();
        expect($response->json('data.api_secret'))->toBeNull();
    });

    it('updates a courier config', function () {
        $config = CourierConfig::factory()->create([
            'courier_code' => 'vnpost',
            'name'         => 'VN Post',
            'is_active'    => false,
        ]);

        $response = $this->putJson("/api/v1/courier-configs/{$config->id}", [
            'is_active'       => true,
            'pickup_hub_code' => 'HCM01',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('courier_configs', [
            'id'        => $config->id,
            'is_active' => true,
        ]);
    });

    it('returns 422 when creating with duplicate courier code', function () {
        CourierConfig::factory()->create(['courier_code' => 'ghn']);

        $response = $this->postJson('/api/v1/courier-configs', [
            'courier_code' => 'ghn',
            'name'         => 'Duplicate GHN',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });

    it('deletes a courier config', function () {
        $config = CourierConfig::factory()->create([
            'courier_code' => 'delete-me',
            'name'         => 'Delete Me Courier',
        ]);

        $response = $this->deleteJson("/api/v1/courier-configs/{$config->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('courier_configs', ['id' => $config->id]);
    });
});
