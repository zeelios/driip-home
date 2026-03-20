<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Actions;

use App\Domain\Warehouse\Data\UpdateWarehouseDto;
use App\Domain\Warehouse\Models\Warehouse;

/**
 * Action: update an existing warehouse's details.
 *
 * Only updates fields that are explicitly provided in the DTO.
 * The warehouse code cannot be changed after creation.
 */
class UpdateWarehouseAction
{
    /**
     * Execute the warehouse update.
     *
     * @param  UpdateWarehouseDto  $dto        Validated update data.
     * @param  Warehouse           $warehouse  The warehouse to update.
     *
     * @return Warehouse  The updated warehouse with manager loaded.
     */
    public function execute(UpdateWarehouseDto $dto, Warehouse $warehouse): Warehouse
    {
        $data = array_filter([
            'name'       => $dto->name,
            'type'       => $dto->type,
            'address'    => $dto->address,
            'province'   => $dto->province,
            'phone'      => $dto->phone,
            'manager_id' => $dto->managerId,
            'notes'      => $dto->notes,
        ], fn ($value) => $value !== null);

        // Handle is_active explicitly since false would be filtered out by array_filter.
        if ($dto->isActive !== null) {
            $data['is_active'] = $dto->isActive;
        }

        $warehouse->update($data);

        return $warehouse->fresh('manager');
    }
}
