<?php

declare(strict_types=1);

use App\Domain\Product\Models\Brand;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('ProductController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        Sanctum::actingAs($this->admin);

        $this->brand    = Brand::factory()->create(['name' => 'Driip', 'slug' => 'driip']);
        $this->category = Category::factory()->create(['name' => 'Áo', 'slug' => 'ao']);
    });

    it('lists products with filters', function () {
        Product::factory()->count(3)->create(['status' => 'active']);
        Product::factory()->count(2)->create(['status' => 'draft']);

        $response = $this->getJson('/api/v1/products?filter[status]=active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status'],
                ],
                'meta',
            ]);

        $statuses = collect($response->json('data'))->pluck('status');
        $statuses->each(fn ($s) => expect($s)->toBe('active'));
    });

    it('creates a product', function () {
        $response = $this->postJson('/api/v1/products', [
            'name'              => 'Áo Thun Driip Classic',
            'slug'              => 'ao-thun-driip-classic',
            'brand_id'          => $this->brand->id,
            'category_id'       => $this->category->id,
            'description'       => 'Áo thun cổ tròn chất liệu cotton cao cấp',
            'short_description' => 'Áo thun Driip Classic',
            'gender'            => 'unisex',
            'status'            => 'active',
            'tags'              => ['streetwear', 'cotton', 'unisex'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Áo Thun Driip Classic')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('products', [
            'name'   => 'Áo Thun Driip Classic',
            'slug'   => 'ao-thun-driip-classic',
            'status' => 'active',
        ]);
    });

    it('shows a product with variants', function () {
        $product = Product::factory()->create([
            'name'   => 'Quần Jean Driip',
            'status' => 'active',
        ]);

        ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
            'status'     => 'active',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Quần Jean Driip')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'variants'],
            ]);

        expect(count($response->json('data.variants')))->toBe(2);
    });

    it('updates a product', function () {
        $product = Product::factory()->create([
            'name'   => 'Sản Phẩm Cũ',
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name'   => 'Sản Phẩm Mới',
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Sản Phẩm Mới')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('products', [
            'id'     => $product->id,
            'name'   => 'Sản Phẩm Mới',
            'status' => 'active',
        ]);
    });

    it('soft deletes a product', function () {
        $product = Product::factory()->create(['status' => 'active']);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    });

    it('returns 404 when product is not found', function () {
        $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    });
});
