<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Actions;

use App\Domain\Coupon\Data\CreateCouponDto;
use App\Domain\Coupon\Models\Coupon;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for persisting a new coupon to the database.
 *
 * Validates that the coupon code is unique before insertion and throws
 * a ValidationException if a conflict is detected so the API layer can
 * surface a 422 response.
 */
class CreateCouponAction
{
    /**
     * Execute the coupon creation.
     *
     * @param  CreateCouponDto  $dto  Validated coupon data.
     * @return Coupon                  The newly created coupon instance.
     *
     * @throws ValidationException  If the coupon code is already taken.
     */
    public function execute(CreateCouponDto $dto): Coupon
    {
        if (Coupon::withTrashed()->where('code', $dto->code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["The coupon code '{$dto->code}' is already in use."],
            ]);
        }

        return Coupon::create([
            'code'                  => $dto->code,
            'name'                  => $dto->name,
            'description'           => $dto->description,
            'type'                  => $dto->type,
            'value'                 => $dto->value,
            'min_order_amount'      => $dto->minOrderAmount,
            'min_items'             => $dto->minItems,
            'max_discount_amount'   => $dto->maxDiscountAmount,
            'applies_to'            => $dto->appliesTo,
            'applies_to_ids'        => $dto->appliesToIds,
            'max_uses'              => $dto->maxUses,
            'max_uses_per_customer' => $dto->maxUsesPerCustomer,
            'is_public'             => $dto->isPublic,
            'is_active'             => $dto->isActive,
            'starts_at'             => $dto->startsAt,
            'expires_at'            => $dto->expiresAt,
            'created_by'            => $dto->createdBy,
        ]);
    }
}
