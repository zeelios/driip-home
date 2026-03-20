<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Actions;

use App\Domain\SaleEvent\Data\CreateSaleEventDto;
use App\Domain\SaleEvent\Models\SaleEvent;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for persisting a new sale event to the database.
 *
 * Validates that the slug is unique before insertion and throws a
 * ValidationException if a conflict is detected so the API layer can
 * surface a 422 response.
 */
class CreateSaleEventAction
{
    /**
     * Execute the sale event creation.
     *
     * @param  CreateSaleEventDto  $dto  Validated sale event data.
     * @return SaleEvent                  The newly created sale event instance with items loaded.
     *
     * @throws ValidationException  If the slug is already taken.
     */
    public function execute(CreateSaleEventDto $dto): SaleEvent
    {
        if (SaleEvent::withTrashed()->where('slug', $dto->slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["The slug '{$dto->slug}' is already in use."],
            ]);
        }

        $saleEvent = SaleEvent::create([
            'name'             => $dto->name,
            'slug'             => $dto->slug,
            'description'      => $dto->description,
            'type'             => $dto->type,
            'status'           => $dto->status,
            'starts_at'        => $dto->startsAt,
            'ends_at'          => $dto->endsAt,
            'max_orders_total' => $dto->maxOrdersTotal,
            'is_public'        => $dto->isPublic,
            'banner_url'       => $dto->bannerUrl,
            'created_by'       => $dto->createdBy,
        ]);

        return $saleEvent->load('items');
    }
}
