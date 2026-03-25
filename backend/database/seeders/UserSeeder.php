<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Create default admin user with super-admin role.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@driip.io'],
            [
                'name' => 'admin',
                'email' => 'admin@driip.io',
                'password' => Hash::make('password'),
            ],
        );

        // assign the highest role
        $user->syncRoles(['super-admin']);
    }
}
