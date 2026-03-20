<?php

declare(strict_types=1);

use App\Domain\Staff\Models\User;
use App\Domain\Tax\Models\TaxConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('TaxConfigController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists tax configs', function () {
        TaxConfig::factory()->count(2)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/tax/configs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'rate', 'is_active'],
                ],
            ]);

        expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
    });

    it('creates a tax config', function () {
        $response = $this->postJson('/api/v1/tax/configs', [
            'name'           => 'VAT 10% - Tiêu chuẩn',
            'rate'           => 10,
            'effective_from' => '2026-01-01',
            'is_active'      => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'VAT 10% - Tiêu chuẩn');

        $this->assertDatabaseHas('tax_configs', [
            'name'      => 'VAT 10% - Tiêu chuẩn',
            'is_active' => true,
        ]);
    });

    it('updates a tax config', function () {
        $config = TaxConfig::factory()->create([
            'name'      => 'VAT 8%',
            'rate'      => 8.00,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/tax/configs/{$config->id}", [
            'name'      => 'VAT 8% - Giảm theo Nghị quyết',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'VAT 8% - Giảm theo Nghị quyết');

        $this->assertDatabaseHas('tax_configs', [
            'id'   => $config->id,
            'name' => 'VAT 8% - Giảm theo Nghị quyết',
        ]);
    });

    it('shows a tax config', function () {
        $config = TaxConfig::factory()->create([
            'name'      => 'VAT 0% - Xuất khẩu',
            'rate'      => 0.00,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/tax/configs/{$config->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $config->id);
    });

    it('deletes a tax config', function () {
        $config = TaxConfig::factory()->create([
            'name' => 'Tax to Delete',
            'rate' => 5.00,
        ]);

        $response = $this->deleteJson("/api/v1/tax/configs/{$config->id}");

        $response->assertStatus(204);
    });
});
