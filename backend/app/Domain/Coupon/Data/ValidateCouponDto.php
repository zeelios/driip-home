<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Data;

/**
 * Data Transfer Object for validating a coupon code at checkout.
 *
 * Carries the context needed for ValidateCouponAction to determine
 * eligibility and compute the discount amount for a specific order.
 */
readonly class ValidateCouponDto
{
    /**
     * @param  string       $code         The raw coupon code entered by the customer.
     * @param  string|null  $customerId   UUID of the authenticated customer, if any.
     * @param  int          $orderAmount  Basket total in VND (before any discount).
     * @param  int          $itemCount    Number of line-item units in the basket.
     */
    public function __construct(
        public string  $code,
        public ?string $customerId,
        public int     $orderAmount,
        public int     $itemCount = 1,
    ) {}

    /**
     * Build a ValidateCouponDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code:        $data['code'],
            customerId:  $data['customer_id'] ?? null,
            orderAmount: (int) $data['order_amount'],
            itemCount:   isset($data['item_count']) ? (int) $data['item_count'] : 1,
        );
    }
}
