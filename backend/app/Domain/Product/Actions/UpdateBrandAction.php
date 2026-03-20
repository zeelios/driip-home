<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Brand;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing brand's fields.
 *
 * If a slug is included in the payload, it is validated for uniqueness
 * against all other brands (excluding the current record).
 */
class UpdateBrandAction
{
    /**
     * Execute the brand update.
     *
     * @param  Brand                $brand  The brand model to update.
     * @param  array<string,mixed>  $data   Validated partial brand data.
     * @return Brand                         The refreshed brand instance after update.
     *
     * @throws ValidationException  If the provided slug is already taken by another brand.
     */
    public function execute(Brand $brand, array $data): Brand
    {
        if (isset($data['slug'])) {
            $conflictExists = Brand::withTrashed()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $brand->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'slug' => ["The slug '{$data['slug']}' is already in use."],
                ]);
            }
        }

        $brand->update($data);

        return $brand->fresh();
    }
}
