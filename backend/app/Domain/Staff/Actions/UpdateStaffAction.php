<?php

declare(strict_types=1);

namespace App\Domain\Staff\Actions;

use App\Domain\Staff\Data\UpdateStaffDto;
use App\Domain\Staff\Exceptions\StaffEmailAlreadyExistsException;
use App\Domain\Staff\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Update an existing staff member's details and optionally sync their roles.
 *
 * Only non-null fields on the DTO are applied to the model, allowing
 * partial updates without overwriting unrelated fields.
 */
class UpdateStaffAction
{
    /**
     * Execute the staff update.
     *
     * @param  UpdateStaffDto  $dto   The update payload.
     * @param  User            $user  The staff member to update.
     * @return User  The updated user model with roles loaded.
     *
     * @throws StaffEmailAlreadyExistsException  If the new email is already taken by another user.
     * @throws \Throwable  On any other failure.
     */
    public function execute(UpdateStaffDto $dto, User $user): User
    {
        if ($dto->email !== null && $dto->email !== $user->email) {
            if (User::where('email', $dto->email)->where('id', '!=', $user->id)->exists()) {
                throw new StaffEmailAlreadyExistsException($dto->email);
            }
        }

        return DB::transaction(function () use ($dto, $user): User {
            $fields = array_filter([
                'name'       => $dto->name,
                'email'      => $dto->email,
                'phone'      => $dto->phone,
                'department' => $dto->department,
                'position'   => $dto->position,
                'status'     => $dto->status,
                'hired_at'   => $dto->hiredAt,
                'notes'      => $dto->notes,
            ], fn (mixed $value): bool => $value !== null);

            if (!empty($fields)) {
                $user->update($fields);
            }

            if ($dto->roles !== null) {
                $user->syncRoles($dto->roles);
            }

            return $user->fresh(['roles']) ?? $user->load('roles');
        });
    }
}
