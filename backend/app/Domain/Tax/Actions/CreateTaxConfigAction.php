<?php

declare(strict_types=1);

namespace App\Domain\Tax\Actions;

use App\Domain\Tax\Data\CreateTaxConfigDto;
use App\Domain\Tax\Models\TaxConfig;

/**
 * Action to create a new tax rate configuration record.
 *
 * Persists a TaxConfig row from the validated DTO. The caller is
 * responsible for ensuring business rules (e.g. no overlapping active
 * periods) before invoking this action.
 */
class CreateTaxConfigAction
{
    /**
     * Execute the tax config creation.
     *
     * @param  CreateTaxConfigDto  $dto  Validated configuration data.
     * @return TaxConfig                 The newly created TaxConfig record.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(CreateTaxConfigDto $dto): TaxConfig
    {
        /** @var TaxConfig $config */
        $config = TaxConfig::create([
            'name'           => $dto->name,
            'rate'           => $dto->rate,
            'applies_to'     => $dto->appliesTo,
            'applies_to_ids' => $dto->appliesToIds,
            'effective_from' => $dto->effectiveFrom,
            'effective_to'   => $dto->effectiveTo,
            'is_active'      => $dto->isActive,
            'created_at'     => now(),
        ]);

        return $config;
    }
}
