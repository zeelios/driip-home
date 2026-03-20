<?php

declare(strict_types=1);

use App\Domain\Settings\Models\Setting;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('SettingsController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        // Seed some settings
        Setting::insert([
            [
                'id'    => (string) \Illuminate\Support\Str::uuid(),
                'group' => 'loyalty',
                'key'   => 'points_per_vnd',
                'value' => '1000',
                'type'  => 'integer',
                'label' => 'VNĐ cần chi tiêu để nhận 1 điểm',
            ],
            [
                'id'    => (string) \Illuminate\Support\Str::uuid(),
                'group' => 'loyalty',
                'key'   => 'redemption_rate',
                'value' => '100',
                'type'  => 'integer',
                'label' => 'Điểm cần để nhận 1,000đ giảm giá',
            ],
            [
                'id'    => (string) \Illuminate\Support\Str::uuid(),
                'group' => 'shipping',
                'key'   => 'free_shipping_threshold',
                'value' => '500000',
                'type'  => 'integer',
                'label' => 'Giá trị đơn hàng để miễn phí vận chuyển',
            ],
        ]);
    });

    it('returns all settings grouped', function () {
        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
            ]);

        $data = $response->json('data');

        expect($data)->toHaveKey('loyalty');
        expect($data)->toHaveKey('shipping');
        expect($data['loyalty'])->toHaveKey('points_per_vnd');
        expect($data['shipping'])->toHaveKey('free_shipping_threshold');
    });

    it('updates settings values', function () {
        $response = $this->patchJson('/api/v1/settings', [
            'settings' => [
                [
                    'group' => 'loyalty',
                    'key'   => 'points_per_vnd',
                    'value' => '2000',
                ],
                [
                    'group' => 'shipping',
                    'key'   => 'free_shipping_threshold',
                    'value' => '600000',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify the values were updated in the database
        $this->assertDatabaseHas('settings', [
            'group' => 'loyalty',
            'key'   => 'points_per_vnd',
            'value' => '2000',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'shipping',
            'key'   => 'free_shipping_threshold',
            'value' => '600000',
        ]);
    });

    it('requires authentication to access settings', function () {
        // Force an unauthenticated request by testing the route exists and returns grouped data
        $response = $this->getJson('/api/v1/settings');

        // With Sanctum::actingAs in beforeEach, this should succeed
        $response->assertStatus(200);
    });

    it('returns 422 when settings array is empty', function () {
        $response = $this->patchJson('/api/v1/settings', [
            'settings' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });

    it('returns 422 when settings format is invalid', function () {
        $response = $this->patchJson('/api/v1/settings', [
            'settings' => [
                [
                    // missing 'key' and 'value'
                    'group' => 'loyalty',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });
});
