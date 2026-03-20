<?php

declare(strict_types=1);

namespace App\Domain\Staff\Data;

/**
 * Data Transfer Object for updating an existing staff member.
 *
 * All fields are optional; only non-null values will be applied to the model.
 * Password changes are handled through a separate dedicated flow.
 */
readonly class UpdateStaffDto
{
    /**
     * @param string|null $name       Updated full name.
     * @param string|null $email      Updated email address (must remain unique).
     * @param string|null $phone      Updated phone number.
     * @param string|null $department Updated department.
     * @param string|null $position   Updated job position/title.
     * @param string|null $status     Updated status (active, inactive, terminated).
     * @param string|null $hiredAt    Updated hire date (YYYY-MM-DD).
     * @param string|null $notes      Updated internal notes.
     * @param array<int,string>|null $roles Role names to sync; null means no change.
     */
    public function __construct(
        public ?string $name       = null,
        public ?string $email      = null,
        public ?string $phone      = null,
        public ?string $department = null,
        public ?string $position   = null,
        public ?string $status     = null,
        public ?string $hiredAt    = null,
        public ?string $notes      = null,
        public ?array  $roles      = null,
    ) {}

    /**
     * Build from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:       $data['name'] ?? null,
            email:      $data['email'] ?? null,
            phone:      $data['phone'] ?? null,
            department: $data['department'] ?? null,
            position:   $data['position'] ?? null,
            status:     $data['status'] ?? null,
            hiredAt:    $data['hired_at'] ?? null,
            notes:      $data['notes'] ?? null,
            roles:      $data['roles'] ?? null,
        );
    }
}
