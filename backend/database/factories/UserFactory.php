<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory for generating Staff User model instances with Vietnamese data.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = User::class;

    /** @var string|null Cached hashed password to avoid rehashing on every call. */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $lastNames  = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $maleFirst  = ['Văn A', 'Văn Bình', 'Văn Cường', 'Minh Đức', 'Quốc Huy', 'Trung Kiên', 'Anh Tuấn', 'Đức Long', 'Hữu Nam', 'Văn Phong'];
        $femaleFirst = ['Thị Lan', 'Thị Hoa', 'Thị Mai', 'Thị Thu', 'Thị Hương', 'Ngọc Ánh', 'Kim Oanh', 'Thanh Thảo', 'Bích Ngọc', 'Minh Châu'];

        $lastName  = $this->faker->randomElement($lastNames);
        $firstName = $this->faker->randomElement(array_merge($maleFirst, $femaleFirst));
        $fullName  = "{$lastName} {$firstName}";

        $departments = ['management', 'sales', 'warehouse', 'cs', 'marketing'];
        $positions   = ['Manager', 'Staff', 'Supervisor', 'Lead', 'Executive'];

        $phonePrefix = $this->faker->randomElement(['090', '091', '093', '094', '096', '097', '098', '032', '033', '034', '035', '036', '037', '038', '039', '070', '079', '077', '076', '078']);

        return [
            'employee_code' => sprintf('DRP-EMP-%03d', $counter),
            'name'          => $fullName,
            'email'         => $this->faker->unique()->safeEmail(),
            'phone'         => $phonePrefix . $this->faker->numerify('#######'),
            'password'      => static::$password ??= Hash::make('password'),
            'department'    => $this->faker->randomElement($departments),
            'position'      => $this->faker->randomElement($positions),
            'status'        => 'active',
            'hired_at'      => $this->faker->dateTimeBetween('2022-01-01', '2024-12-31')->format('Y-m-d'),
            'avatar'        => null,
            'terminated_at' => null,
            'notes'         => null,
        ];
    }

    /**
     * State for an inactive staff member.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * State for a terminated staff member.
     *
     * @return static
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'        => 'terminated',
            'terminated_at' => $this->faker->dateTimeBetween('2023-01-01', '2024-12-31')->format('Y-m-d'),
        ]);
    }
}
