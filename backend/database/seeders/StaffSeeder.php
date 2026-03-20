<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default staff accounts for the Driip backend.
 *
 * Creates:
 *  - 1 super-admin (admin@driip.com)
 *  - 1 manager
 *  - 3 warehouse-staff
 *  - 2 sales-staff
 *
 * Uses updateOrCreate keyed on email so it is safe to re-run.
 */
class StaffSeeder extends Seeder
{
    /**
     * Run the staff seeder.
     *
     * @return void
     */
    public function run(): void
    {
        // Super admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@driip.com'],
            [
                'employee_code' => 'DRP-EMP-001',
                'name'          => 'Admin Driip',
                'phone'         => '0901234567',
                'password'      => Hash::make('password'),
                'department'    => 'IT',
                'position'      => 'Manager',
                'status'        => 'active',
                'hired_at'      => '2022-01-01',
            ],
        );
        $superAdmin->syncRoles(['super-admin']);

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@driip.com'],
            [
                'employee_code' => 'DRP-EMP-002',
                'name'          => 'Nguyễn Thị Lan',
                'phone'         => '0912345678',
                'password'      => Hash::make('password'),
                'department'    => 'Sales',
                'position'      => 'Manager',
                'status'        => 'active',
                'hired_at'      => '2022-03-15',
            ],
        );
        $manager->syncRoles(['manager']);

        // Warehouse staff (3)
        $warehouseStaff = [
            [
                'employee_code' => 'DRP-EMP-003',
                'name'          => 'Trần Văn Bình',
                'email'         => 'warehouse1@driip.com',
                'phone'         => '0923456789',
                'department'    => 'Warehouse',
                'position'      => 'Staff',
                'hired_at'      => '2022-06-01',
            ],
            [
                'employee_code' => 'DRP-EMP-004',
                'name'          => 'Lê Thị Hoa',
                'email'         => 'warehouse2@driip.com',
                'phone'         => '0934567890',
                'department'    => 'Warehouse',
                'position'      => 'Staff',
                'hired_at'      => '2023-01-10',
            ],
            [
                'employee_code' => 'DRP-EMP-005',
                'name'          => 'Phạm Minh Đức',
                'email'         => 'warehouse3@driip.com',
                'phone'         => '0945678901',
                'department'    => 'Warehouse',
                'position'      => 'Supervisor',
                'hired_at'      => '2022-09-01',
            ],
        ];

        foreach ($warehouseStaff as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'status'   => 'active',
                ]),
            );
            $user->syncRoles(['warehouse-staff']);
        }

        // Sales staff (2)
        $salesStaff = [
            [
                'employee_code' => 'DRP-EMP-006',
                'name'          => 'Hoàng Quốc Huy',
                'email'         => 'sales1@driip.com',
                'phone'         => '0956789012',
                'department'    => 'Sales',
                'position'      => 'Staff',
                'hired_at'      => '2023-04-01',
            ],
            [
                'employee_code' => 'DRP-EMP-007',
                'name'          => 'Vũ Thị Mai',
                'email'         => 'sales2@driip.com',
                'phone'         => '0967890123',
                'department'    => 'Sales',
                'position'      => 'Lead',
                'hired_at'      => '2022-11-15',
            ],
        ];

        foreach ($salesStaff as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'status'   => 'active',
                ]),
            );
            $user->syncRoles(['sales-staff']);
        }
    }
}
