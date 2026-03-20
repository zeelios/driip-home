<?php

declare(strict_types=1);

use App\Domain\Staff\Models\SalaryRecord;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('SalaryController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->staffMember = User::factory()->create([
            'name'   => 'Phạm Văn Được',
            'status' => 'active',
        ]);
    });

    it('records salary payment for staff', function () {
        $response = $this->postJson("/api/v1/staff/{$this->staffMember->id}/salary/pay", [
            'period'         => '2026-03',
            'base_salary'    => 12000000,
            'allowances'     => [500000, 300000],
            'bonuses'        => [1000000],
            'deductions'     => [200000],
            'overtime_hours' => 8,
            'overtime_rate'  => 75000,
            'payment_method' => 'bank_transfer',
            'paid_at'        => '2026-03-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'period', 'base_salary', 'total_gross', 'total_net'],
            ]);

        $this->assertDatabaseHas('salary_records', [
            'user_id' => $this->staffMember->id,
            'period'  => '2026-03',
        ]);
    });

    it('lists salary records for staff', function () {
        SalaryRecord::create([
            'user_id'        => $this->staffMember->id,
            'period'         => '2026-01',
            'base_salary'    => 10000000,
            'allowances'     => [],
            'bonuses'        => [],
            'deductions'     => [],
            'overtime_hours' => 0,
            'overtime_rate'  => 0,
            'total_gross'    => 10000000,
            'total_net'      => 10000000,
            'created_by'     => $this->admin->id,
        ]);

        SalaryRecord::create([
            'user_id'        => $this->staffMember->id,
            'period'         => '2026-02',
            'base_salary'    => 10000000,
            'allowances'     => [],
            'bonuses'        => [],
            'deductions'     => [],
            'overtime_hours' => 0,
            'overtime_rate'  => 0,
            'total_gross'    => 10000000,
            'total_net'      => 10000000,
            'created_by'     => $this->admin->id,
        ]);

        $response = $this->getJson("/api/v1/staff/{$this->staffMember->id}/salary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'period', 'base_salary', 'total_gross', 'total_net'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(2);
    });

    it('returns 422 when period format is invalid', function () {
        $response = $this->postJson("/api/v1/staff/{$this->staffMember->id}/salary/pay", [
            'period'      => '03-2026', // wrong format
            'base_salary' => 10000000,
        ]);

        $response->assertStatus(422);
    });

    it('calculates total correctly', function () {
        $response = $this->postJson("/api/v1/staff/{$this->staffMember->id}/salary/pay", [
            'period'         => '2026-03',
            'base_salary'    => 10000000,
            'allowances'     => [1000000, 500000],
            'bonuses'        => [2000000],
            'deductions'     => [500000, 300000],
            'overtime_hours' => 0,
            'overtime_rate'  => 0,
        ]);

        $response->assertStatus(201);

        // total_gross = 10_000_000 + 1_000_000 + 500_000 + 2_000_000 = 13_500_000
        expect($response->json('data.total_gross'))->toBe(13500000);
        // total_net = 13_500_000 - 500_000 - 300_000 = 12_700_000
        expect($response->json('data.total_net'))->toBe(12700000);
    });
});
