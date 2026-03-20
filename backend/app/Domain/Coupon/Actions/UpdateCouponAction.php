<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Actions;

use App\Domain\Coupon\Data\UpdateCouponDto;
use App\Domain\Coupon\Models\Coupon;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing coupon's fields.
 *
 * If the DTO contains a new code that differs from the current one, it is
 * validated for uniqueness against all coupons (including soft-deleted).
 */
class UpdateCouponAction
{
    /**
     * Execute the coupon update.
     *
     * @param  UpdateCouponDto  $dto     Validated partial coupon data.
     * @param  Coupon           $coupon  The coupon model to update.
     * @return Coupon                     The refreshed coupon instance.
     *
     * @throws ValidationException  If the provided code is already taken by another coupon.
     */
    public function execute(UpdateCouponDto $dto, Coupon $coupon): Coupon
    {
        $updateData = $dto->toUpdateArray();

        if (isset($updateData['code']) && $updateData['code'] !== $coupon->code) {
            $conflictExists = Coupon::withTrashed()
                ->where('code', $updateData['code'])
                ->where('id', '!=', $coupon->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'code' => ["The coupon code '{$updateData['code']}' is already in use."],
                ]);
            }
        }

        $coupon->update($updateData);

        return $coupon->fresh();
    }
}
