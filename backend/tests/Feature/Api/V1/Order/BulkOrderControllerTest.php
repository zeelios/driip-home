<?php

declare(strict_types=1);

use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;
use App\Jobs\BulkCancelOrdersJob;
use App\Jobs\BulkConfirmOrdersJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('BulkOrderController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        Queue::fake();
    });

    it('bulk confirms multiple orders', function () {
        $orders = Order::factory()->count(3)->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $orderIds = $orders->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/orders/bulk/confirm', [
            'order_ids' => $orderIds,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('queued', true)
            ->assertJsonPath('count', 3)
            ->assertJsonStructure(['queued', 'job_id', 'count']);

        Queue::assertPushed(BulkConfirmOrdersJob::class);
    });

    it('bulk cancels multiple orders', function () {
        $orders = Order::factory()->count(2)->create([
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $orderIds = $orders->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/orders/bulk/cancel', [
            'order_ids' => $orderIds,
            'reason'    => 'Hủy hàng loạt theo yêu cầu quản lý',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('queued', true)
            ->assertJsonPath('count', 2)
            ->assertJsonStructure(['queued', 'job_id', 'count']);

        Queue::assertPushed(BulkCancelOrdersJob::class);
    });

    it('returns a job_id for tracking', function () {
        $response = $this->postJson('/api/v1/orders/bulk/confirm', [
            'order_ids' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('queued', true);

        $jobId = $response->json('job_id');
        expect($jobId)->not->toBeNull();
        expect(strlen($jobId))->toBeGreaterThan(0);
    });

    it('bulk ships multiple orders', function () {
        $orders = Order::factory()->count(2)->create([
            'status'         => 'packed',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->postJson('/api/v1/orders/bulk/ship', [
            'order_ids'    => $orders->pluck('id')->toArray(),
            'courier_code' => 'GHN',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('queued', true)
            ->assertJsonPath('count', 2);
    });
});
