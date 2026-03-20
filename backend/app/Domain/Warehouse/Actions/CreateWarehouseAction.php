<?php

declare(strict_types=1);

namespace App\Domain\Warehouse\Actions;

use App\Domain\Shared\Traits\GeneratesCode;
use App\Domain\Warehouse\Data\CreateWarehouseDto;
use App\Domain\Warehouse\Models\Warehouse;

/**
 * Action: create a new warehouse with a generated code.
 *
 * Generates a sequential warehouse code (DRP-WH-001) and persists the
 * warehouse record to the database.
 */
class CreateWarehouseAction
{
    use GeneratesCode;

    /**
     * Execute the warehouse creation.
     *
     * @param  CreateWarehouseDto  $dto  Validated data for the new warehouse.
     *
     * @return Warehouse  The newly created warehouse.
     */
    public function execute(CreateWarehouseDto $dto): Warehouse
    {
        $sequence = Warehouse::withTrashed()->count() + 1;

        /** @var Warehouse $warehouse */
        $warehouse = Warehouse::create([
            'code'       => $this->buildCode('DRP-WH', $sequence, 3),
            'name'       => $dto->name,
            'type'       => $dto->type,
            'address'    => $dto->address,
            'province'   => $dto->province,
            'phone'      => $dto->phone,
            'manager_id' => $dto->managerId,
            'is_active'  => $dto->isActive ?? true,
            'notes'      => $dto->notes,
        ]);

        return $warehouse->load('manager');
    }
}
