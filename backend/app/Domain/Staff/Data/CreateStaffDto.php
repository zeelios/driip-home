<?php

declare(strict_types=1);

namespace App\Domain\Staff\Data;

/**
 * Data Transfer Object for creating a new staff member.
 */
readonly class CreateStaffDto
{
    /**
     * @param string  $name       Full name of the staff member.
     * @param string  $email      Unique email address.
     * @param string  $password   Plain-text password (will be hashed by the model).
     * @param string|null $phone  Optional phone number.
     * @param string|null $department Optional department (management, sales, etc.).
     * @param string|null $position   Optional job position/title.
     * @param string|null $hiredAt    Optional hire date (YYYY-MM-DD).
     * @param string|null $notes      Optional internal notes.
     * @param array<int,string> $roles  Role names to assign on creation.
     */
    public function __construct(
        public string  $name,
        public string  $email,
        public string  $password,
        public ?string $phone,
        public ?string $department,
        public ?string $position,
        public ?string $hiredAt,
        public ?string $notes,
        public array   $roles = [],
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
            name:       $data['name'],
            email:      $data['email'],
            password:   $data['password'],
            phone:      $data['phone'] ?? null,
            department: $data['department'] ?? null,
            position:   $data['position'] ?? null,
            hiredAt:    $data['hired_at'] ?? null,
            notes:      $data['notes'] ?? null,
            roles:      $data['roles'] ?? [],
        );
    }
}
