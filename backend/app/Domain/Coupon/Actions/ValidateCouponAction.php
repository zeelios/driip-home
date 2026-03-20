<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Actions;

use App\Domain\Coupon\Data\ValidateCouponDto;
use App\Domain\Coupon\Models\Coupon;

/**
 * Action responsible for validating a coupon code against a prospective order.
 *
 * Returns a structured result array rather than throwing exceptions so that
 * calling code (controller, checkout service) can present human-readable
 * messages without catching exceptions.
 *
 * Result shape:
 * {
 *   "valid":           bool,
 *   "coupon":          Coupon|null,
 *   "discount_amount": int,   // in VND
 *   "message":         string
 * }
 */
class ValidateCouponAction
{
    /**
     * Validate a coupon code against the supplied order context.
     *
     * Steps:
     * 1. Look up the coupon by code.
     * 2. Check global validity (active, usage limit, date window).
     * 3. Check per-customer usage limit.
     * 4. Check minimum order amount.
     * 5. Check minimum item count.
     * 6. Calculate the discount amount for the given type.
     *
     * @param  ValidateCouponDto  $dto  Order context for validation.
     * @return array{valid: bool, coupon: Coupon|null, discount_amount: int, message: string}
     */
    public function execute(ValidateCouponDto $dto): array
    {
        $coupon = Coupon::where('code', $dto->code)->first();

        if ($coupon === null) {
            return $this->invalid(null, 'Coupon code not found.');
        }

        if (!$coupon->isValid()) {
            if ($coupon->isExpired()) {
                return $this->invalid($coupon, 'This coupon has expired.');
            }

            if (!$coupon->is_active) {
                return $this->invalid($coupon, 'This coupon is not active.');
            }

            return $this->invalid($coupon, 'This coupon has reached its usage limit.');
        }

        if ($dto->customerId !== null && $coupon->max_uses_per_customer > 0) {
            $customerUsageCount = $coupon->usages()
                ->where('customer_id', $dto->customerId)
                ->count();

            if ($customerUsageCount >= $coupon->max_uses_per_customer) {
                return $this->invalid(
                    $coupon,
                    "You have already used this coupon {$coupon->max_uses_per_customer} time(s).",
                );
            }
        }

        if ($coupon->min_order_amount !== null && $dto->orderAmount < $coupon->min_order_amount) {
            $formatted = number_format($coupon->min_order_amount);

            return $this->invalid(
                $coupon,
                "A minimum order of {$formatted} VND is required to use this coupon.",
            );
        }

        if ($coupon->min_items !== null && $dto->itemCount < $coupon->min_items) {
            return $this->invalid(
                $coupon,
                "A minimum of {$coupon->min_items} item(s) is required to use this coupon.",
            );
        }

        $discountAmount = $this->calculateDiscount($coupon, $dto->orderAmount);

        return [
            'valid'           => true,
            'coupon'          => $coupon,
            'discount_amount' => $discountAmount,
            'message'         => 'Coupon applied successfully.',
        ];
    }

    /**
     * Calculate the discount amount in VND based on the coupon type.
     *
     * @param  Coupon  $coupon       The coupon model.
     * @param  int     $orderAmount  The order total in VND.
     * @return int                   Discount amount in VND (never negative).
     */
    private function calculateDiscount(Coupon $coupon, int $orderAmount): int
    {
        return match ($coupon->type) {
            'percent' => $this->percentDiscount($coupon, $orderAmount),
            'fixed_amount' => $this->fixedDiscount($coupon),
            'free_shipping' => 0, // Shipping discount handled at order level
            default => 0,
        };
    }

    /**
     * Calculate a percentage-based discount, respecting the max_discount_amount cap.
     *
     * @param  Coupon  $coupon
     * @param  int     $orderAmount
     * @return int
     */
    private function percentDiscount(Coupon $coupon, int $orderAmount): int
    {
        $raw = (int) round($orderAmount * ((float) $coupon->value / 100.0));

        if ($coupon->max_discount_amount !== null) {
            return min($raw, $coupon->max_discount_amount);
        }

        return $raw;
    }

    /**
     * Calculate a fixed-amount discount, capped at the order total.
     *
     * @param  Coupon  $coupon
     * @return int
     */
    private function fixedDiscount(Coupon $coupon): int
    {
        return (int) $coupon->value;
    }

    /**
     * Build a standard "invalid" result array.
     *
     * @param  Coupon|null  $coupon
     * @param  string       $message
     * @return array{valid: bool, coupon: Coupon|null, discount_amount: int, message: string}
     */
    private function invalid(?Coupon $coupon, string $message): array
    {
        return [
            'valid'           => false,
            'coupon'          => $coupon,
            'discount_amount' => 0,
            'message'         => $message,
        ];
    }
}
