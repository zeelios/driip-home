<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customer\Models\Customer;
use App\Domain\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Order model instances with Vietnamese shipping data.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $lastNames  = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi'];
        $firstNames = ['Văn An', 'Thị Lan', 'Minh Đức', 'Thị Hoa', 'Quốc Huy', 'Thị Mai', 'Trung Kiên', 'Ngọc Ánh'];
        $shippingName = $this->faker->randomElement($lastNames) . ' ' . $this->faker->randomElement($firstNames);

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039']);
        $shippingPhone = $phonePrefix . $this->faker->numerify('#######');

        $provinces = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương', 'Đồng Nai'];
        $districts = ['Quận 1', 'Quận 3', 'Quận Hoàn Kiếm', 'Quận Đống Đa', 'Quận Hải Châu'];
        $streetNames = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Điện Biên Phủ', 'Cách Mạng Tháng 8', 'Lý Thường Kiệt'];

        $subtotal      = $this->faker->numberBetween(150000, 5000000);
        $shippingFee   = $this->faker->randomElement([0, 25000, 30000, 35000]);
        $totalBeforeTax = $subtotal + $shippingFee;
        $totalAfterTax  = $totalBeforeTax;

        $date = $this->faker->dateTimeBetween('-6 months', 'now');
        $orderNumber = 'DRP-' . $date->format('ymd') . '-' . sprintf('%04d', $counter);

        return [
            'order_number'        => $orderNumber,
            'customer_id'         => Customer::factory(),
            'guest_name'          => null,
            'guest_email'         => null,
            'guest_phone'         => null,
            'status'              => 'pending',
            'payment_status'      => 'unpaid',
            'payment_method'      => $this->faker->randomElement(['cod', 'bank_transfer', 'momo', 'vnpay']),
            'payment_reference'   => null,
            'paid_at'             => null,
            'subtotal'            => $subtotal,
            'coupon_id'           => null,
            'coupon_code'         => null,
            'coupon_discount'     => 0,
            'loyalty_points_used' => 0,
            'loyalty_discount'    => 0,
            'shipping_fee'        => $shippingFee,
            'vat_rate'            => 0,
            'vat_amount'          => 0,
            'total_before_tax'    => $totalBeforeTax,
            'total_after_tax'     => $totalAfterTax,
            'tax_code'            => null,
            'cost_total'          => (int) round($subtotal * 0.6),
            'shipping_name'       => $shippingName,
            'shipping_phone'      => $shippingPhone,
            'shipping_province'   => $this->faker->randomElement($provinces),
            'shipping_district'   => $this->faker->randomElement($districts),
            'shipping_ward'       => 'Phường ' . $this->faker->numberBetween(1, 25),
            'shipping_address'    => $this->faker->numberBetween(1, 500) . ' ' . $this->faker->randomElement($streetNames),
            'shipping_zip'        => null,
            'notes'               => $this->faker->optional(0.2)->sentence(),
            'internal_notes'      => null,
            'tags'                => [],
            'source'              => $this->faker->randomElement(['website', 'facebook', 'tiktok', 'instagram', 'pos']),
            'utm_source'          => null,
            'utm_medium'          => null,
            'utm_campaign'        => null,
            'warehouse_id'        => null,
            'assigned_to'         => null,
            'packed_by'           => null,
            'packed_at'           => null,
            'confirmed_at'        => null,
            'delivered_at'        => null,
            'cancelled_at'        => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * State for a confirmed order.
     *
     * @return static
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * State for a delivered, paid order.
     *
     * @return static
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => 'delivered',
            'payment_status' => 'paid',
            'confirmed_at'   => now()->subDays(5),
            'delivered_at'   => now()->subDays(2),
            'paid_at'        => now()->subDays(2),
        ]);
    }

    /**
     * State for a cancelled order.
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => 'Khách hàng yêu cầu hủy đơn',
        ]);
    }
}
