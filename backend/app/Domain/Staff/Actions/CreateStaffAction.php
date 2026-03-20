<?php

declare(strict_types=1);

namespace App\Domain\Staff\Actions;

use App\Domain\Shared\Traits\GeneratesCode;
use App\Domain\Staff\Data\CreateStaffDto;
use App\Domain\Staff\Exceptions\StaffEmailAlreadyExistsException;
use App\Domain\Staff\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create a new staff member and assign roles.
 *
 * Wraps the operation in a transaction so any failure
 * rolls back both the user row and the role assignment.
 */
class CreateStaffAction
{
    use GeneratesCode;

    /**
     * Execute the staff creation.
     *
     * @param  CreateStaffDto  $dto
     * @return User  The newly created user with roles loaded.
     *
     * @throws StaffEmailAlreadyExistsException  If email already exists.
     * @throws \Throwable  On any other failure.
     */
    public function execute(CreateStaffDto $dto): User
    {
        if (User::where('email', $dto->email)->exists()) {
            throw new StaffEmailAlreadyExistsException($dto->email);
        }

        return DB::transaction(function () use ($dto): User {
            $sequence = User::withTrashed()->count() + 1;

            $user = User::create([
                'employee_code' => $this->buildCode('DRP-EMP', $sequence, 3),
                'name'          => $dto->name,
                'email'         => $dto->email,
                'password'      => $dto->password,
                'phone'         => $dto->phone,
                'department'    => $dto->department,
                'position'      => $dto->position,
                'hired_at'      => $dto->hiredAt,
                'notes'         => $dto->notes,
                'status'        => 'active',
            ]);

            if (!empty($dto->roles)) {
                $user->syncRoles($dto->roles);
            }

            return $user->load('profile');
        });
    }
}
