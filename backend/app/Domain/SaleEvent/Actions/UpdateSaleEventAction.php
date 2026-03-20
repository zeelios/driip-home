<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Actions;

use App\Domain\SaleEvent\Data\UpdateSaleEventDto;
use App\Domain\SaleEvent\Models\SaleEvent;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing sale event's fields.
 *
 * If the DTO contains a new slug that differs from the current one, it is
 * validated for uniqueness against all sale events (including soft-deleted).
 */
class UpdateSaleEventAction
{
    /**
     * Execute the sale event update.
     *
     * @param  UpdateSaleEventDto  $dto        Validated partial sale event data.
     * @param  SaleEvent           $saleEvent  The sale event model to update.
     * @return SaleEvent                         The refreshed sale event instance with items loaded.
     *
     * @throws ValidationException  If the provided slug is already taken by another sale event.
     */
    public function execute(UpdateSaleEventDto $dto, SaleEvent $saleEvent): SaleEvent
    {
        $updateData = $dto->toUpdateArray();

        if (isset($updateData['slug']) && $updateData['slug'] !== $saleEvent->slug) {
            $conflictExists = SaleEvent::withTrashed()
                ->where('slug', $updateData['slug'])
                ->where('id', '!=', $saleEvent->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'slug' => ["The slug '{$updateData['slug']}' is already in use."],
                ]);
            }
        }

        $saleEvent->update($updateData);

        return $saleEvent->fresh()->load('items');
    }
}
