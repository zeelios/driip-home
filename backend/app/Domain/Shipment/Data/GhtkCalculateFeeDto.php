<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * DTO for GHTK Calculate Fee request.
 *
 * Represents parameters for GET /services/shipment/fee
 */
class GhtkCalculateFeeDto
{
    /**
     * @param  string  $pickProvince  Pickup province
     * @param  string  $pickDistrict  Pickup district
     * @param  string  $province  Delivery province
     * @param  string  $district  Delivery district
     * @param  string  $address  Delivery address
     * @param  int  $weight  Weight in grams
     * @param  int  $value  Declared value (VND)
     * @param  string|null  $transport  Transport type: 'fly' or 'road'
     * @param  string|null  $deliverOption  Delivery option: 'xteam', 'xfast', etc.
     * @param  array<int>|null  $tags  Order tags
     * @param  array<string>|null  $productDimensions  Product dimensions for volume-based pricing
     */
    public function __construct(
        public readonly string $pickProvince,
        public readonly string $pickDistrict,
        public readonly string $province,
        public readonly string $district,
        public readonly string $address,
        public readonly int $weight = 1000,
        public readonly int $value = 0,
        public readonly ?string $transport = null,
        public readonly ?string $deliverOption = null,
        public readonly ?array $tags = null,
        public readonly ?array $productDimensions = null,
    ) {
    }

    /**
     * Create from pickup and delivery addresses.
     *
     * @param  array<string,string>  $pickup
     * @param  array<string,string>  $delivery
     * @param  int  $weight
     * @param  int  $value
     * @return self
     */
    public static function fromAddresses(
        array $pickup,
        array $delivery,
        int $weight = 1000,
        int $value = 0
    ): self {
        return new self(
            pickProvince: $pickup['province'] ?? 'Hà Nội',
            pickDistrict: $pickup['district'] ?? 'Cầu Giấy',
            province: $delivery['province'] ?? 'Hồ Chí Minh',
            district: $delivery['district'] ?? 'Quận 1',
            address: $delivery['address'] ?? '',
            weight: $weight,
            value: $value,
            transport: $delivery['transport'] ?? null,
            deliverOption: $delivery['deliver_option'] ?? null,
        );
    }

    /**
     * Convert to query array for API request.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $data = [
            'pick_province' => $this->pickProvince,
            'pick_district' => $this->pickDistrict,
            'province' => $this->province,
            'district' => $this->district,
            'address' => $this->address,
            'weight' => $this->weight,
            'value' => $this->value,
        ];

        if ($this->transport) {
            $data['transport'] = $this->transport;
        }
        if ($this->deliverOption) {
            $data['deliver_option'] = $this->deliverOption;
        }
        if ($this->tags) {
            $data['tags'] = $this->tags;
        }

        return $data;
    }
}
