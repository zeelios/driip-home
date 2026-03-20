<?php

declare(strict_types=1);

use App\Domain\Product\Models\Category;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CategoryController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);
    });

    it('lists categories', function () {
        Category::factory()->count(4)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'meta',
            ]);

        expect($response->json('meta.total'))->toBe(4);
    });

    it('creates a category', function () {
        $response = $this->postJson('/api/v1/categories', [
            'name'        => 'Áo Nam',
            'slug'        => 'ao-nam',
            'description' => 'Danh mục áo nam thời trang',
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Áo Nam')
            ->assertJsonPath('data.slug', 'ao-nam');

        $this->assertDatabaseHas('categories', [
            'name' => 'Áo Nam',
            'slug' => 'ao-nam',
        ]);
    });

    it('creates a subcategory with parent_id', function () {
        $parent = Category::factory()->create([
            'name' => 'Áo Nam',
            'slug' => 'ao-nam',
        ]);

        $response = $this->postJson('/api/v1/categories', [
            'name'      => 'Áo Polo Nam',
            'slug'      => 'ao-polo-nam',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Áo Polo Nam');

        $this->assertDatabaseHas('categories', [
            'name'      => 'Áo Polo Nam',
            'parent_id' => $parent->id,
        ]);
    });

    it('updates a category', function () {
        $category = Category::factory()->create([
            'name'      => 'Quần Cũ',
            'slug'      => 'quan-cu',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name'      => 'Quần Dài',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Quần Dài');

        $this->assertDatabaseHas('categories', [
            'id'        => $category->id,
            'name'      => 'Quần Dài',
            'is_active' => false,
        ]);
    });

    it('shows a category with its children', function () {
        $parent = Category::factory()->create([
            'name' => 'Phụ Kiện',
            'slug' => 'phu-kien',
        ]);

        Category::factory()->create([
            'name'      => 'Thắt Lưng',
            'slug'      => 'that-lung',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson("/api/v1/categories/{$parent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $parent->id)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'children'],
            ]);

        expect(count($response->json('data.children')))->toBe(1);
        expect($response->json('data.children.0.name'))->toBe('Thắt Lưng');
    });

    it('returns 422 when creating with duplicate slug', function () {
        Category::factory()->create(['slug' => 'ao-nam']);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Another Category',
            'slug' => 'ao-nam',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false);
    });
});
