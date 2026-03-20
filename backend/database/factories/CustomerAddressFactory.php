<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating CustomerAddress model instances with Vietnamese address data.
 *
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = CustomerAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastNames   = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi'];
        $firstNames  = ['Văn An', 'Thị Lan', 'Minh Đức', 'Thị Hoa', 'Quốc Huy', 'Thị Mai', 'Trung Kiên', 'Ngọc Ánh', 'Anh Tuấn', 'Kim Oanh'];
        $name        = $this->faker->randomElement($lastNames) . ' ' . $this->faker->randomElement($firstNames);

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039']);
        $phone       = $phonePrefix . $this->faker->numerify('#######');

        $addressData = $this->getVietnameseAddress();

        $labels = ['Nhà', 'Công ty', 'Nhà người thân', null];

        return [
            'customer_id'    => Customer::factory(),
            'label'          => $this->faker->randomElement($labels),
            'recipient_name' => $name,
            'phone'          => $phone,
            'province'       => $addressData['province'],
            'district'       => $addressData['district'],
            'ward'           => $addressData['ward'],
            'address'        => $addressData['address'],
            'zip_code'       => null,
            'is_default'     => false,
        ];
    }

    /**
     * State for the default address.
     *
     * @return static
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Generate a realistic Vietnamese address.
     *
     * @return array<string, string>
     */
    private function getVietnameseAddress(): array
    {
        $locations = [
            [
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận 1',
                'ward'     => 'Phường Bến Nghé',
            ],
            [
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận 3',
                'ward'     => 'Phường Võ Thị Sáu',
            ],
            [
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận 7',
                'ward'     => 'Phường Tân Phú',
            ],
            [
                'province' => 'TP. Hồ Chí Minh',
                'district' => 'Quận Bình Thạnh',
                'ward'     => 'Phường 25',
            ],
            [
                'province' => 'Hà Nội',
                'district' => 'Quận Hoàn Kiếm',
                'ward'     => 'Phường Hàng Bài',
            ],
            [
                'province' => 'Hà Nội',
                'district' => 'Quận Đống Đa',
                'ward'     => 'Phường Láng Hạ',
            ],
            [
                'province' => 'Hà Nội',
                'district' => 'Quận Cầu Giấy',
                'ward'     => 'Phường Dịch Vọng',
            ],
            [
                'province' => 'Đà Nẵng',
                'district' => 'Quận Hải Châu',
                'ward'     => 'Phường Thạch Thang',
            ],
            [
                'province' => 'Hải Phòng',
                'district' => 'Quận Lê Chân',
                'ward'     => 'Phường An Biên',
            ],
            [
                'province' => 'Cần Thơ',
                'district' => 'Quận Ninh Kiều',
                'ward'     => 'Phường An Hội',
            ],
        ];

        $location    = $this->faker->randomElement($locations);
        $streetNum   = $this->faker->numberBetween(1, 500);
        $streetNames = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Điện Biên Phủ', 'Cách Mạng Tháng 8', 'Lý Thường Kiệt', 'Bà Triệu', 'Hai Bà Trưng', 'Phan Chu Trinh', 'Ngô Quyền', 'Võ Văn Tần', 'Nam Kỳ Khởi Nghĩa'];

        return [
            'province' => $location['province'],
            'district' => $location['district'],
            'ward'     => $location['ward'],
            'address'  => "{$streetNum} " . $this->faker->randomElement($streetNames),
        ];
    }
}
