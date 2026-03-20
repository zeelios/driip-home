<?php

declare(strict_types=1);

use App\Domain\Staff\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('AuthController', function () {

    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
    });

    it('logs in with valid credentials', function () {
        $user = User::factory()->create([
            'email'    => 'nhan.vien@driip.com',
            'password' => Hash::make('mat-khau-manh'),
            'status'   => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nhan.vien@driip.com',
            'password' => 'mat-khau-manh',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'token', 'data']);

        expect($response->json('token'))->not->toBeNull();
    });

    it('returns 401 with invalid credentials', function () {
        User::factory()->create([
            'email'    => 'nhan.vien@driip.com',
            'password' => Hash::make('mat-khau-manh'),
            'status'   => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nhan.vien@driip.com',
            'password' => 'sai-mat-khau',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('data.success', false)
            ->assertJsonStructure(['data' => ['success', 'request_code', 'message']]);
    });

    it('returns 403 when account is inactive', function () {
        User::factory()->create([
            'email'    => 'nghi-viec@driip.com',
            'password' => Hash::make('password123'),
            'status'   => 'terminated',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nghi-viec@driip.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('data.success', false);
    });

    it('returns 422 when email is missing', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.success', false)
            ->assertJsonStructure(['data' => ['success', 'request_code', 'message']]);
    });

    it('returns authenticated user via /me endpoint', function () {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    });

    it('logs out successfully', function () {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('cannot access protected routes without token', function () {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    });

    it('cannot access general protected routes without token', function () {
        $response = $this->getJson('/api/v1/staff');

        $response->assertStatus(401);
    });
});
