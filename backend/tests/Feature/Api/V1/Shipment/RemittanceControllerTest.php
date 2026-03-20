<?php

declare(strict_types=1);

use App\Domain\Shipment\Models\CourierCODRemittance;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('RemittanceController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists cod remittances', function () {
        CourierCODRemittance::factory()->count(2)->create([
            'courier_code' => 'ghn',
            'status'       => 'pending',
        ]);

        $response = $this->getJson('/api/v1/courier-remittances');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'courier_code', 'status', 'total_cod_collected'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(2);
    });

    it('shows a remittance with items', function () {
        $remittance = CourierCODRemittance::factory()->create([
            'courier_code'        => 'ghn',
            'remittance_reference' => 'GHN-REM-2026-001',
            'period_from'         => '2026-03-01',
            'period_to'           => '2026-03-15',
            'total_cod_collected' => 15000000,
            'total_fees_deducted' => 750000,
            'net_remittance'      => 14250000,
            'status'              => 'pending',
        ]);

        $response = $this->getJson("/api/v1/courier-remittances/{$remittance->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $remittance->id)
            ->assertJsonPath('data.courier_code', 'ghn')
            ->assertJsonPath('data.total_cod_collected', 15000000)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'courier_code',
                    'remittance_reference',
                    'total_cod_collected',
                    'total_fees_deducted',
                    'net_remittance',
                    'status',
                    'items',
                ],
            ]);
    });

    it('filters remittances by courier code', function () {
        CourierCODRemittance::factory()->count(2)->create([
            'courier_code' => 'ghn',
            'status'       => 'pending',
        ]);
        CourierCODRemittance::factory()->count(1)->create([
            'courier_code' => 'ghtk',
            'status'       => 'pending',
        ]);

        $response = $this->getJson('/api/v1/courier-remittances?courier_code=ghn');

        $response->assertStatus(200);

        $courierCodes = collect($response->json('data'))->pluck('courier_code');
        $courierCodes->each(fn ($code) => expect($code)->toBe('ghn'));
    });

    it('filters remittances by status', function () {
        CourierCODRemittance::factory()->create([
            'courier_code' => 'ghn',
            'status'       => 'reconciled',
        ]);
        CourierCODRemittance::factory()->create([
            'courier_code' => 'ghn',
            'status'       => 'pending',
        ]);

        $response = $this->getJson('/api/v1/courier-remittances?status=reconciled');

        $response->assertStatus(200);

        $statuses = collect($response->json('data'))->pluck('status');
        $statuses->each(fn ($s) => expect($s)->toBe('reconciled'));
    });
});
