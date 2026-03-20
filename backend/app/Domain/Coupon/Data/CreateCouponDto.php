<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Data;

/**
 * Data Transfer Object for creating a new coupon.
 *
 * Carries all fields required to persist a coupon record.
 */
readonly class CreateCouponDto
{
    /**
     * @param  string              $code                  Unique coupon code (e.g. SUMMER20).
     * @param  string              $name                  Human-readable display name.
     * @param  string|null         $description           Optional marketing description.
     * @param  string              $type                  Discount type: percent, fixed_amount, free_shipping.
     * @param  float               $value                 Discount value (percentage or VND amount).
     * @param  int|null            $minOrderAmount        Minimum order total in VND to apply coupon.
     * @param  int|null            $minItems              Minimum number of line items required.
     * @param  int|null            $maxDiscountAmount     Cap on the discount amount in VND.
     * @param  string              $appliesTo             Scope: all, category, product, brand.
     * @param  array<int,string>   $appliesToIds          UUIDs of scoped entities.
     * @param  int|null            $maxUses               Global usage cap.
     * @param  int                 $maxUsesPerCustomer    Per-customer usage cap.
     * @param  bool                $isPublic              Whether the coupon is visible publicly.
     * @param  bool                $isActive              Whether the coupon is currently enabled.
     * @param  string|null         $startsAt              ISO-8601 datetime from which the coupon is valid.
     * @param  string|null         $expiresAt             ISO-8601 datetime after which the coupon expires.
     * @param  string|null         $createdBy             UUID of the staff user creating this coupon.
     */
    public function __construct(
        public string  $code,
        public string  $name,
        public ?string $description,
        public string  $type,
        public float   $value,
        public ?int    $minOrderAmount,
        public ?int    $minItems,
        public ?int    $maxDiscountAmount,
        public string  $appliesTo          = 'all',
        public array   $appliesToIds       = [],
        public ?int    $maxUses            = null,
        public int     $maxUsesPerCustomer = 1,
        public bool    $isPublic           = false,
        public bool    $isActive           = true,
        public ?string $startsAt           = null,
        public ?string $expiresAt          = null,
        public ?string $createdBy          = null,
    ) {}

    /**
     * Build a CreateCouponDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @param  string|null          $createdBy  UUID of the authenticated staff user.
     * @return self
     */
    public static function fromArray(array $data, ?string $createdBy = null): self
    {
        return new self(
            code:                $data['code'],
            name:                $data['name'],
            description:         $data['description'] ?? null,
            type:                $data['type'],
            value:               (float) $data['value'],
            minOrderAmount:      isset($data['min_order_amount']) ? (int) $data['min_order_amount'] : null,
            minItems:            isset($data['min_items']) ? (int) $data['min_items'] : null,
            maxDiscountAmount:   isset($data['max_discount_amount']) ? (int) $data['max_discount_amount'] : null,
            appliesTo:           $data['applies_to'] ?? 'all',
            appliesToIds:        $data['applies_to_ids'] ?? [],
            maxUses:             isset($data['max_uses']) ? (int) $data['max_uses'] : null,
            maxUsesPerCustomer:  isset($data['max_uses_per_customer']) ? (int) $data['max_uses_per_customer'] : 1,
            isPublic:            (bool) ($data['is_public'] ?? false),
            isActive:            (bool) ($data['is_active'] ?? true),
            startsAt:            $data['starts_at'] ?? null,
            expiresAt:           $data['expires_at'] ?? null,
            createdBy:           $createdBy,
        );
    }
}
