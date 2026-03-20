<?php

declare(strict_types=1);

use App\Domain\Order\Models\Order;
use App\Domain\Shipment\Models\Shipment;
use App\Domain\Shipment\Models\ShipmentTrackingEvent;
use App\Domain\Shipment\Services\GHNService;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('ShipmentController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->order = Order::factory()->create([
            'status'         => 'packed',
            'payment_status' => 'unpaid',
        ]);

        $this->shipment = Shipment::create([
            'order_id'        => $this->order->id,
            'courier_code'    => 'ghn',
            'tracking_number' => 'GHN12345678',
            'status'          => 'created',
            'cod_amount'      => 250000,
            'cod_collected'   => false,
            'failed_attempts' => 0,
            'created_by'      => $this->admin->id,
        ]);
    });

    it('lists shipments', function () {
        $response = $this->getJson('/api/v1/shipments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'courier_code', 'tracking_number', 'status'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(1);
    });

    it('shows a shipment with tracking events', function () {
        ShipmentTrackingEvent::create([
            'shipment_id'    => $this->shipment->id,
            'status'         => 'picked_up',
            'message'        => 'Đã lấy hàng tại địa chỉ người gửi',
            'location'       => 'Hà Nội',
            'occurred_at'    => now()->subHours(2),
            'synced_at'      => now(),
        ]);

        ShipmentTrackingEvent::create([
            'shipment_id'    => $this->shipment->id,
            'status'         => 'in_transit',
            'message'        => 'Hàng đang được vận chuyển',
            'location'       => 'Bưu cục Hà Nội',
            'occurred_at'    => now()->subHour(),
            'synced_at'      => now(),
        ]);

        $response = $this->getJson("/api/v1/shipments/{$this->shipment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->shipment->id)
            ->assertJsonPath('data.tracking_number', 'GHN12345678')
            ->assertJsonStructure([
                'data' => ['id', 'courier_code', 'tracking_number', 'status', 'tracking_events'],
            ]);

        expect(count($response->json('data.tracking_events')))->toBe(2);
    });

    it('syncs tracking for a shipment', function () {
        $this->mock(GHNService::class, function ($mock) {
            $mock->shouldReceive('getTrackingEvents')
                ->andReturn([
                    [
                        'status'              => 'delivered',
                        'message'             => 'Giao hàng thành công',
                        'location'            => 'TP. Hồ Chí Minh',
                        'occurred_at'         => now()->toIso8601String(),
                        'courier_status_code' => null,
                    ],
                ]);
        });

        $response = $this->postJson("/api/v1/shipments/{$this->shipment->id}/track");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->shipment->id);
    });

    it('returns 404 for a non-existent shipment', function () {
        $response = $this->getJson('/api/v1/shipments/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    });

    it('filters shipments by courier code', function () {
        Shipment::create([
            'order_id'        => $this->order->id,
            'courier_code'    => 'ghtk',
            'tracking_number' => 'GHTK99999999',
            'status'          => 'created',
            'cod_amount'      => 0,
            'cod_collected'   => false,
            'failed_attempts' => 0,
            'created_by'      => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/shipments?filter[courier_code]=ghn');

        $response->assertStatus(200);

        $courierCodes = collect($response->json('data'))->pluck('courier_code');
        $courierCodes->each(fn ($code) => expect($code)->toBe('ghn'));
    });
});
