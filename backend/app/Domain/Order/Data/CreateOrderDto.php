<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Data Transfer Object for creating a new customer order.
 *
 * Supports both registered customers (via customerId) and anonymous
 * guest shoppers (via the guest* fields). At least one of the two
 * identification strategies must be populated by the caller.
 */
readonly class CreateOrderDto
{
    /**
     * Create a new CreateOrderDto.
     *
     * @param  string|null                $customerId            UUID of the registered customer, or null for guest.
     * @param  string|null                $guestName             Full name of the guest shopper.
     * @param  string|null                $guestEmail            Email address of the guest shopper.
     * @param  string|null                $guestPhone            Phone number of the guest shopper.
     * @param  string|null                $paymentMethod         Payment method enum value.
     * @param  list<CreateOrderItemDto>   $items                 Line items to include in the order.
     * @param  string|null                $couponCode            Optional coupon code to apply.
     * @param  int                        $loyaltyPointsToUse   Number of loyalty points to redeem.
     * @param  string|null                $warehouseId           UUID of the fulfilling warehouse.
     * @param  string                     $shippingName          Recipient full name.
     * @param  string                     $shippingPhone         Recipient contact phone number.
     * @param  string                     $shippingProvince      Province / city name.
     * @param  string|null                $shippingDistrict      District name.
     * @param  string|null                $shippingWard          Ward / commune name.
     * @param  string                     $shippingAddress       Street-level address line.
     * @param  string|null                $shippingZip           Postal/ZIP code.
     * @param  string|null                $notes                 Customer-visible delivery notes.
     * @param  string                     $source                Origin channel (e.g. 'admin', 'web').
     * @param  string|null                $utmSource             UTM source tracking parameter.
     * @param  string|null                $utmMedium             UTM medium tracking parameter.
     * @param  string|null                $utmCampaign           UTM campaign tracking parameter.
     */
    public function __construct(
        public ?string $customerId,
        public ?string $guestName,
        public ?string $guestEmail,
        public ?string $guestPhone,
        public ?string $paymentMethod,
        public array   $items,
        public ?string $couponCode,
        public int     $loyaltyPointsToUse = 0,
        public ?string $warehouseId = null,
        public string  $shippingName = '',
        public string  $shippingPhone = '',
        public string  $shippingProvince = '',
        public ?string $shippingDistrict = null,
        public ?string $shippingWard = null,
        public string  $shippingAddress = '',
        public ?string $shippingZip = null,
        public ?string $notes = null,
        public string  $source = 'admin',
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
    ) {}
}
