<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Data;

/**
 * Auto-generated DTO for AssignStaffRequest.
 * Source of truth: validation rules in AssignStaffRequest.
 */
readonly class AssignStaffDto
{
    public function __construct(
        public string $userId,
        public string $role
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            role: $data['role']
        );
    }
}
