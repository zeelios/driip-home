<?php

declare(strict_types=1);

namespace App\Domain\Tax\Actions;

use App\Domain\Tax\Models\TaxConfig;

/**
 * Action to update an existing tax rate configuration.
 *
 * Accepts a partial update payload so callers may update only the
 * fields they need to change without providing the full record.
 */
class UpdateTaxConfigAction
{
    /**
     * Execute the tax config update.
     *
     * Only the keys present in $data are applied; all other fields
     * are left unchanged.
     *
     * @param  TaxConfig             $config  The existing config record to update.
     * @param  array<string,mixed>   $data    Partial update payload (validated by the caller).
     * @return TaxConfig                      The refreshed TaxConfig record.
     *
     * @throws \Throwable  On any database failure.
     */
    public function execute(TaxConfig $config, array $data): TaxConfig
    {
        $allowed = [
            'name',
            'rate',
            'applies_to',
            'applies_to_ids',
            'effective_from',
            'effective_to',
            'is_active',
        ];

        $config->update(array_intersect_key($data, array_flip($allowed)));

        return $config->refresh();
    }
}
