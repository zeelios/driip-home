<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating StaffProfile model instances with Vietnamese personal data.
 *
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = StaffProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastNames  = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi'];
        $maleFirst  = ['Văn Bình', 'Văn Cường', 'Minh Đức', 'Quốc Huy', 'Trung Kiên', 'Anh Tuấn', 'Đức Long'];
        $femaleFirst = ['Thị Lan', 'Thị Hoa', 'Thị Mai', 'Thị Thu', 'Ngọc Ánh', 'Kim Oanh', 'Thanh Thảo'];

        $lastName  = $this->faker->randomElement($lastNames);
        $firstName = $this->faker->randomElement(array_merge($maleFirst, $femaleFirst));
        $emergencyName = "{$lastName} {$firstName}";

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039']);
        $emergencyPhone = $phonePrefix . $this->faker->numerify('#######');

        $banks    = ['Vietcombank', 'VietinBank', 'Techcombank', 'MB Bank', 'ACB', 'BIDV'];
        $provinces = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương', 'Đồng Nai', 'Hà Nam', 'Nam Định', 'Nghệ An'];

        $streetNumbers = $this->faker->numberBetween(1, 500);
        $streetNames   = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Điện Biên Phủ', 'Cách Mạng Tháng 8', 'Lý Thường Kiệt', 'Bà Triệu', 'Hai Bà Trưng', 'Phan Chu Trinh', 'Ngô Quyền'];
        $districts     = ['Quận 1', 'Quận 2', 'Quận 3', 'Quận 7', 'Quận 10', 'Quận Hoàn Kiếm', 'Quận Đống Đa', 'Quận Cầu Giấy', 'Quận Thanh Xuân', 'Quận Bình Thạnh'];

        $province = $this->faker->randomElement($provinces);
        $address  = "{$streetNumbers} {$this->faker->randomElement($streetNames)}, {$this->faker->randomElement($districts)}";

        $issuedBy = $this->faker->randomElement(['Công an TP. Hồ Chí Minh', 'Công an Hà Nội', 'Công an Đà Nẵng', 'Công an Hải Phòng', 'Cục Cảnh sát ĐKQL cư trú và DLQG về dân cư']);

        return [
            'user_id'                   => User::factory(),
            'id_card_number'            => $this->faker->numerify('############'),
            'id_card_issued_at'         => $this->faker->dateTimeBetween('2015-01-01', '2022-12-31')->format('Y-m-d'),
            'id_card_issued_by'         => $issuedBy,
            'date_of_birth'             => $this->faker->dateTimeBetween('1985-01-01', '2000-12-31')->format('Y-m-d'),
            'gender'                    => $this->faker->randomElement(['male', 'female']),
            'address'                   => $address,
            'province'                  => $province,
            'bank_name'                 => $this->faker->randomElement($banks),
            'bank_account_number'       => $this->faker->numerify('##########'),
            'bank_account_name'         => strtoupper($this->faker->randomElement($lastNames) . ' ' . $this->faker->randomElement(array_merge($maleFirst, $femaleFirst))),
            'emergency_contact_name'    => $emergencyName,
            'emergency_contact_phone'   => $emergencyPhone,
        ];
    }
}
