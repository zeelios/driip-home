<?php

declare(strict_types=1);

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerInteraction;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CustomerInteractionController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->customer = Customer::factory()->create([
            'first_name' => 'Võ',
            'last_name'  => 'Thị Mai',
        ]);
    });

    it('logs a customer interaction', function () {
        $response = $this->postJson("/api/v1/customers/{$this->customer->id}/interactions", [
            'type'         => 'call',
            'channel'      => 'phone',
            'summary'      => 'Khách gọi hỏi về đơn hàng #DRP-2026-001',
            'outcome'      => 'resolved',
            'follow_up_at' => '2026-03-25 10:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'type', 'channel', 'summary', 'outcome'],
            ])
            ->assertJsonPath('data.type', 'call')
            ->assertJsonPath('data.channel', 'phone');

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $this->customer->id,
            'type'        => 'call',
        ]);
    });

    it('lists customer interactions', function () {
        CustomerInteraction::create([
            'customer_id' => $this->customer->id,
            'type'        => 'email',
            'channel'     => 'email',
            'summary'     => 'Phản hồi email khiếu nại',
            'outcome'     => 'pending',
            'created_by'  => $this->admin->id,
            'created_at'  => now(),
        ]);

        CustomerInteraction::create([
            'customer_id' => $this->customer->id,
            'type'        => 'chat',
            'channel'     => 'zalo',
            'summary'     => 'Hỗ trợ qua Zalo về đổi hàng',
            'outcome'     => 'resolved',
            'created_by'  => $this->admin->id,
            'created_at'  => now(),
        ]);

        $response = $this->getJson("/api/v1/customers/{$this->customer->id}/interactions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'channel', 'summary'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(2);
    });

    it('returns 422 when type is missing', function () {
        $response = $this->postJson("/api/v1/customers/{$this->customer->id}/interactions", [
            'channel' => 'phone',
            'summary' => 'Missing type field',
        ]);

        $response->assertStatus(422);
    });

    it('only shows interactions for the specified customer', function () {
        $anotherCustomer = Customer::factory()->create();

        CustomerInteraction::create([
            'customer_id' => $this->customer->id,
            'type'        => 'call',
            'summary'     => 'Cuộc gọi từ khách hàng',
            'created_by'  => $this->admin->id,
            'created_at'  => now(),
        ]);

        CustomerInteraction::create([
            'customer_id' => $anotherCustomer->id,
            'type'        => 'email',
            'summary'     => 'Email từ khách hàng khác',
            'created_by'  => $this->admin->id,
            'created_at'  => now(),
        ]);

        $response = $this->getJson("/api/v1/customers/{$this->customer->id}/interactions");

        $response->assertStatus(200);
        expect($response->json('meta.total'))->toBe(1);

        $customerIds = collect($response->json('data'))->pluck('customer_id');
        $customerIds->each(fn ($id) => expect($id)->toBe($this->customer->id));
    });
});
