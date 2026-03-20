<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Customer model instances with Vietnamese data.
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $lastNames    = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $maleFirst    = ['Văn An', 'Văn Bình', 'Minh Đức', 'Quốc Huy', 'Trung Kiên', 'Anh Tuấn', 'Đức Long', 'Hữu Nam', 'Văn Phong', 'Thanh Tùng'];
        $femaleFirst  = ['Thị Lan', 'Thị Hoa', 'Thị Mai', 'Thị Thu', 'Thị Hương', 'Ngọc Ánh', 'Kim Oanh', 'Thanh Thảo', 'Bích Ngọc', 'Minh Châu'];

        $gender    = $this->faker->randomElement(['male', 'female', null]);
        $lastName  = $this->faker->randomElement($lastNames);
        $firstName = $gender === 'male'
            ? $this->faker->randomElement($maleFirst)
            : ($gender === 'female' ? $this->faker->randomElement($femaleFirst) : $this->faker->randomElement(array_merge($maleFirst, $femaleFirst)));

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039', '070', '079', '077', '076', '078']);
        $phone       = $phonePrefix . $this->faker->unique()->numerify('#######');

        $provinces = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương', 'Đồng Nai', 'Nghệ An', 'Thanh Hóa', 'Bắc Ninh', 'Hà Nam', 'Hưng Yên'];
        $sources    = ['facebook', 'instagram', 'tiktok', 'website', 'referral', 'walk_in'];

        $allTags    = ['vip', 'regular', 'new'];
        $tagCount   = $this->faker->numberBetween(0, 2);
        $tags       = $tagCount > 0 ? $this->faker->randomElements($allTags, $tagCount) : [];

        $totalOrders = $this->faker->numberBetween(0, 10);
        $totalSpent  = $totalOrders > 0 ? $totalOrders * $this->faker->numberBetween(200000, 1500000) : 0;

        return [
            'customer_code'     => sprintf('DRP-C-%05d', $counter),
            'first_name'        => $lastName,
            'last_name'         => $firstName,
            'email'             => $this->faker->optional(0.7)->safeEmail(),
            'phone'             => $phone,
            'phone_verified_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 year', 'now'),
            'gender'            => $gender,
            'date_of_birth'     => $this->faker->optional(0.5)->dateTimeBetween('1980-01-01', '2005-12-31')?->format('Y-m-d'),
            'avatar'            => null,
            'source'            => $this->faker->randomElement($sources),
            'referrer_id'       => null,
            'referral_code'     => strtoupper($this->faker->bothify('????####')),
            'tags'              => $tags,
            'is_blocked'        => false,
            'blocked_reason'    => null,
            'total_orders'      => $totalOrders,
            'total_spent'       => $totalSpent,
            'last_ordered_at'   => $totalOrders > 0 ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'notes'             => null,
            'zalo_id'           => null,
        ];
    }

    /**
     * State for a VIP customer.
     *
     * @return static
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'tags'         => ['vip'],
            'total_orders' => $this->faker->numberBetween(10, 50),
            'total_spent'  => $this->faker->numberBetween(10000000, 100000000),
        ]);
    }

    /**
     * State for a blocked customer.
     *
     * @return static
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked'     => true,
            'blocked_reason' => 'Vi phạm chính sách cửa hàng',
        ]);
    }
}
