<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Data;

/**
 * Data transfer object for updating an existing warehouse.
 *
 * All fields are optional — only non-null values will be applied.
 */
class UpdateWarehouseDto
{
    /**
     * Create a new UpdateWarehouseDto.
     *
     * @param  string|null  $name       New display name.
     * @param  string|null  $type       New type: main, satellite, or dropship.
     * @param  string|null  $address    New address.
     * @param  string|null  $province   New province.
     * @param  string|null  $phone      New phone number.
     * @param  string|null  $managerId  New manager UUID.
     * @param  bool|null    $isActive   New active status.
     * @param  string|null  $notes      New notes.
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $address = null,
        public readonly ?string $province = null,
        public readonly ?string $phone = null,
        public readonly ?string $managerId = null,
        public readonly ?bool   $isActive = null,
        public readonly ?string $notes = null,
    ) {}
}
