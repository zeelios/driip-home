<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Supplier model instances with Vietnamese vendor data.
 *
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $supplierNames = [
            'Công ty TNHH Dệt May Thành Công',
            'Xưởng May Thời Trang Minh Châu',
            'Công ty CP Sản Xuất Phụ Kiện Bình Minh',
            'Nhà Máy May Mặc Đại Việt',
            'Công ty TNHH Xuất Nhập Khẩu Hà Thành',
            'Xưởng May Gia Đình Phúc An',
            'Công ty CP Thời Trang Hải Phát',
        ];

        $lastNames  = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Vũ', 'Đặng', 'Bùi'];
        $firstNames = ['Văn Bình', 'Thị Lan', 'Minh Đức', 'Thị Hoa', 'Quốc Huy', 'Ngọc Ánh', 'Trung Kiên', 'Kim Oanh'];
        $contactName = $this->faker->randomElement($lastNames) . ' ' . $this->faker->randomElement($firstNames);

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039']);
        $phone       = $phonePrefix . $this->faker->numerify('#######');

        $provinces = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Hải Phòng', 'Bình Dương', 'Đồng Nai', 'Long An'];
        $province  = $this->faker->randomElement($provinces);

        $streetNames = ['Nguyễn Văn Linh', 'Lê Văn Việt', 'Trần Phú', 'Điện Biên Phủ', 'Tô Hiến Thành', 'Cộng Hòa'];
        $address     = $this->faker->numberBetween(1, 500) . ' ' . $this->faker->randomElement($streetNames) . ', ' . $province;

        return [
            'code'          => sprintf('DRP-SUP-%03d', $counter),
            'name'          => $this->faker->randomElement($supplierNames),
            'contact_name'  => $contactName,
            'email'         => $this->faker->safeEmail(),
            'phone'         => $phone,
            'address'       => $address,
            'province'      => $province,
            'country'       => 'VN',
            'payment_terms' => $this->faker->randomElement(['NET30', 'NET15', 'COD', 'prepaid']),
            'notes'         => null,
            'is_active'     => true,
        ];
    }

    /**
     * State for an inactive supplier.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
