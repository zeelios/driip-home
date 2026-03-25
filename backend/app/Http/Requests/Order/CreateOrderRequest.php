<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\CreateOrderDto;
use App\Domain\Order\Data\CreateOrderItemDto;
use App\Http\Requests\ApiRequest;

/**
 * Validates the payload for creating a new order.
 *
 * Supports both registered customers and guest shoppers. Requires at
 * least valid shipping details and one or more line items.
 */
class CreateOrderRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller/policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating an order.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id'                     => ['nullable', 'uuid'],
            'guest_name'                      => ['nullable', 'string', 'max:255'],
            'guest_email'                     => ['nullable', 'email', 'max:255'],
            'guest_phone'                     => ['nullable', 'string', 'max:20'],
            'payment_method'                  => ['nullable', 'string'],
            'items'                           => ['required', 'array', 'min:1'],
            'items.*.product_variant_id'      => ['required', 'uuid'],
            'items.*.quantity'                => ['required', 'integer', 'min:1'],
            'items.*.unit_price'              => ['required', 'integer', 'min:0'],
            'coupon_code'                     => ['nullable', 'string'],
            'loyalty_points_to_use'           => ['nullable', 'integer', 'min:0'],
            'warehouse_id'                    => ['nullable', 'uuid'],
            'shipping_name'                   => ['required', 'string', 'max:255'],
            'shipping_phone'                  => ['required', 'string', 'max:20'],
            'shipping_province'               => ['required', 'string', 'max:100'],
            'shipping_district'               => ['nullable', 'string', 'max:100'],
            'shipping_ward'                   => ['nullable', 'string', 'max:100'],
            'shipping_address'                => ['required', 'string'],
            'shipping_zip'                    => ['nullable', 'string', 'max:10'],
            'notes'                           => ['nullable', 'string'],
            'source'                          => ['nullable', 'in:web,facebook,instagram,zalo,manual,admin'],
            'utm_source'                      => ['nullable', 'string', 'max:100'],
            'utm_medium'                      => ['nullable', 'string', 'max:100'],
            'utm_campaign'                    => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Build a CreateOrderDto from the validated request data.
     *
     * @return CreateOrderDto
     */
    public function dto(): CreateOrderDto
    {
        $data = $this->validated();

        $items = array_map(
            fn (array $item) => new CreateOrderItemDto(
                productVariantId: $item['product_variant_id'],
                quantity:         (int) $item['quantity'],
                unitPrice:        (int) $item['unit_price'],
            ),
            $data['items']
        );

        return new CreateOrderDto(
            customerId:           $data['customer_id'] ?? null,
            guestName:            $data['guest_name'] ?? null,
            guestEmail:           $data['guest_email'] ?? null,
            guestPhone:           $data['guest_phone'] ?? null,
            paymentMethod:        $data['payment_method'] ?? null,
            items:                $items,
            couponCode:           $data['coupon_code'] ?? null,
            loyaltyPointsToUse:   (int) ($data['loyalty_points_to_use'] ?? 0),
            warehouseId:          $data['warehouse_id'] ?? null,
            shippingName:         $data['shipping_name'],
            shippingPhone:        $data['shipping_phone'],
            shippingProvince:     $data['shipping_province'],
            shippingDistrict:     $data['shipping_district'] ?? null,
            shippingWard:         $data['shipping_ward'] ?? null,
            shippingAddress:      $data['shipping_address'],
            shippingZip:          $data['shipping_zip'] ?? null,
            notes:                $data['notes'] ?? null,
            source:               $data['source'] ?? 'admin',
            utmSource:            $data['utm_source'] ?? null,
            utmMedium:            $data['utm_medium'] ?? null,
            utmCampaign:          $data['utm_campaign'] ?? null,
        );
    }
}
