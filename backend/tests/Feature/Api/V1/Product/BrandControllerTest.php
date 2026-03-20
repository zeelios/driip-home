<?php

declare(strict_types=1);

use App\Domain\Product\Models\Brand;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('BrandController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists brands', function () {
        Brand::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/brands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('creates a brand', function () {
        $response = $this->postJson('/api/v1/brands', [
            'name'        => 'Driip Streetwear',
            'slug'        => 'driip-streetwear',
            'description' => 'Thương hiệu thời trang đường phố Việt Nam',
            'is_active'   => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Driip Streetwear')
            ->assertJsonPath('data.slug', 'driip-streetwear');

        $this->assertDatabaseHas('brands', [
            'name' => 'Driip Streetwear',
            'slug' => 'driip-streetwear',
        ]);
    });

    it('returns 422 when creating brand with duplicate slug', function () {
        Brand::factory()->create(['slug' => 'existing-brand']);

        $response = $this->postJson('/api/v1/brands', [
            'name' => 'Another Brand',
            'slug' => 'existing-brand',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });

    it('updates a brand', function () {
        $brand = Brand::factory()->create([
            'name' => 'Tên Cũ',
            'slug' => 'ten-cu',
        ]);

        $response = $this->putJson("/api/v1/brands/{$brand->id}", [
            'name'      => 'Tên Mới',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Tên Mới');

        $this->assertDatabaseHas('brands', [
            'id'        => $brand->id,
            'name'      => 'Tên Mới',
            'is_active' => false,
        ]);
    });

    it('deletes a brand', function () {
        $brand = Brand::factory()->create(['name' => 'Brand Xóa']);

        $response = $this->deleteJson("/api/v1/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    });

    it('shows a single brand', function () {
        $brand = Brand::factory()->create([
            'name' => 'Driip Original',
            'slug' => 'driip-original',
        ]);

        $response = $this->getJson("/api/v1/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $brand->id)
            ->assertJsonPath('data.name', 'Driip Original');
    });
});
