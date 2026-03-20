<?php

declare(strict_types=1);

namespace App\Domain\Coupon\Data;

/**
 * Data Transfer Object for updating an existing coupon.
 *
 * All fields are optional. Only non-null values are applied during the update,
 * allowing patch-style partial modifications.
 */
readonly class UpdateCouponDto
{
    /**
     * @param  string|null         $code                  New unique coupon code.
     * @param  string|null         $name                  New display name.
     * @param  string|null         $description           New description.
     * @param  string|null         $type                  New type: percent, fixed_amount, free_shipping.
     * @param  float|null          $value                 New discount value.
     * @param  int|null            $minOrderAmount        New minimum order amount in VND.
     * @param  int|null            $minItems              New minimum item count.
     * @param  int|null            $maxDiscountAmount     New maximum discount cap in VND.
     * @param  string|null         $appliesTo             New scope: all, category, product, brand.
     * @param  array<int,string>|null $appliesToIds       New scoped entity UUIDs.
     * @param  int|null            $maxUses               New global usage cap.
     * @param  int|null            $maxUsesPerCustomer    New per-customer usage cap.
     * @param  bool|null           $isPublic              New public visibility flag.
     * @param  bool|null           $isActive              New active flag.
     * @param  string|null         $startsAt              New start datetime (ISO-8601).
     * @param  string|null         $expiresAt             New expiry datetime (ISO-8601).
     */
    public function __construct(
        public ?string $code               = null,
        public ?string $name               = null,
        public ?string $description        = null,
        public ?string $type               = null,
        public ?float  $value              = null,
        public ?int    $minOrderAmount     = null,
        public ?int    $minItems           = null,
        public ?int    $maxDiscountAmount  = null,
        public ?string $appliesTo          = null,
        public ?array  $appliesToIds       = null,
        public ?int    $maxUses            = null,
        public ?int    $maxUsesPerCustomer = null,
        public ?bool   $isPublic           = null,
        public ?bool   $isActive           = null,
        public ?string $startsAt           = null,
        public ?string $expiresAt          = null,
    ) {}

    /**
     * Build an UpdateCouponDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code:               $data['code'] ?? null,
            name:               $data['name'] ?? null,
            description:        $data['description'] ?? null,
            type:               $data['type'] ?? null,
            value:              isset($data['value']) ? (float) $data['value'] : null,
            minOrderAmount:     isset($data['min_order_amount']) ? (int) $data['min_order_amount'] : null,
            minItems:           isset($data['min_items']) ? (int) $data['min_items'] : null,
            maxDiscountAmount:  isset($data['max_discount_amount']) ? (int) $data['max_discount_amount'] : null,
            appliesTo:          $data['applies_to'] ?? null,
            appliesToIds:       $data['applies_to_ids'] ?? null,
            maxUses:            isset($data['max_uses']) ? (int) $data['max_uses'] : null,
            maxUsesPerCustomer: isset($data['max_uses_per_customer']) ? (int) $data['max_uses_per_customer'] : null,
            isPublic:           isset($data['is_public']) ? (bool) $data['is_public'] : null,
            isActive:           isset($data['is_active']) ? (bool) $data['is_active'] : null,
            startsAt:           $data['starts_at'] ?? null,
            expiresAt:          $data['expires_at'] ?? null,
        );
    }

    /**
     * Return only the non-null values as a key-value array suitable for Model::update().
     *
     * @return array<string,mixed>
     */
    public function toUpdateArray(): array
    {
        $map = [
            'code'                  => $this->code,
            'name'                  => $this->name,
            'description'           => $this->description,
            'type'                  => $this->type,
            'value'                 => $this->value,
            'min_order_amount'      => $this->minOrderAmount,
            'min_items'             => $this->minItems,
            'max_discount_amount'   => $this->maxDiscountAmount,
            'applies_to'            => $this->appliesTo,
            'applies_to_ids'        => $this->appliesToIds,
            'max_uses'              => $this->maxUses,
            'max_uses_per_customer' => $this->maxUsesPerCustomer,
            'is_public'             => $this->isPublic,
            'is_active'             => $this->isActive,
            'starts_at'             => $this->startsAt,
            'expires_at'            => $this->expiresAt,
        ];

        return array_filter($map, fn ($v) => $v !== null);
    }
}
