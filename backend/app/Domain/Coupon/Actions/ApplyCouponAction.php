<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Actions;

use App\Domain\Coupon\Models\Coupon;
use App\Domain\Coupon\Models\CouponUsage;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for finalising a coupon redemption against a confirmed order.
 *
 * Creates a CouponUsage record and atomically increments the coupon's
 * used_count counter. Both writes are wrapped in a transaction.
 *
 * This action assumes the coupon has already been validated by ValidateCouponAction
 * and should only be called at the point of order confirmation.
 */
class ApplyCouponAction
{
    /**
     * Record the coupon redemption and increment the global usage counter.
     *
     * @param  string       $couponId       UUID of the coupon being redeemed.
     * @param  string|null  $customerId     UUID of the customer redeeming the coupon, if known.
     * @param  string       $orderId        UUID of the order the coupon is applied to.
     * @param  int          $discountAmount Actual discount amount in VND applied to the order.
     * @return CouponUsage                  The newly created usage record.
     */
    public function execute(
        string  $couponId,
        ?string $customerId,
        string  $orderId,
        int     $discountAmount = 0,
    ): CouponUsage {
        return DB::transaction(function () use ($couponId, $customerId, $orderId, $discountAmount): CouponUsage {
            /** @var Coupon $coupon */
            $coupon = Coupon::lockForUpdate()->findOrFail($couponId);

            $usage = CouponUsage::create([
                'coupon_id'       => $coupon->id,
                'customer_id'     => $customerId,
                'order_id'        => $orderId,
                'discount_amount' => $discountAmount,
                'used_at'         => now(),
            ]);

            $coupon->increment('used_count');

            return $usage;
        });
    }
}
