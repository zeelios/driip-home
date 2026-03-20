<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Data;

/**
 * Data transfer object for creating a new warehouse.
 */
class CreateWarehouseDto
{
    /**
     * Create a new CreateWarehouseDto.
     *
     * @param  string       $name       Display name of the warehouse.
     * @param  string       $type       Warehouse type: main, satellite, or dropship.
     * @param  string|null  $address    Full address of the warehouse.
     * @param  string|null  $province   Province where the warehouse is located.
     * @param  string|null  $phone      Contact phone number.
     * @param  string|null  $managerId  UUID of the staff user who manages this warehouse.
     * @param  bool|null    $isActive   Whether the warehouse is active (default true).
     * @param  string|null  $notes      Optional notes.
     */
    public function __construct(
        public readonly string  $name,
        public readonly string  $type,
        public readonly ?string $address = null,
        public readonly ?string $province = null,
        public readonly ?string $phone = null,
        public readonly ?string $managerId = null,
        public readonly ?bool   $isActive = true,
        public readonly ?string $notes = null,
    ) {}
}
