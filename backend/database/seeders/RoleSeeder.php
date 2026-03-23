<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seeds the default Spatie permission roles for the Driip backend.
 *
 * Creates the five core roles used to control access throughout the application.
 * Uses firstOrCreate to ensure idempotency across environments.
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the roles seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $roles = [
            'super-admin',
            'admin',
            'manager',
            'warehouse-staff',
            'sales-staff',
        ];

        foreach ($roles as $roleName) {
            Role::create(
                ['name' => $roleName, 'guard_name' => 'web'],
            );
        }
    }
}
