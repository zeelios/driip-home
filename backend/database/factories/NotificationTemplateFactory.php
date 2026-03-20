<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating NotificationTemplate model instances.
 *
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = NotificationTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug    = $this->faker->unique()->slug(2);
        $subject = 'Driip - ' . $this->faker->sentence(5);

        return [
            'slug'      => $slug,
            'name'      => ucfirst(str_replace('-', ' ', $slug)),
            'channel'   => 'email',
            'subject'   => $subject,
            'body_html' => <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>{$subject}</title></head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 32px;">
    <h2>Xin chào {{ name }},</h2>
    <p>{{ message }}</p>
    <p style="color: #888; font-size: 12px; margin-top: 32px;">© Driip</p>
  </div>
</body>
</html>
HTML,
            'variables' => ['name', 'message'],
            'locale'    => 'vi',
            'is_active' => true,
        ];
    }

    /**
     * State for a SMS channel template.
     *
     * @return static
     */
    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel'   => 'sms',
            'subject'   => null,
            'body_html' => 'Driip: {{ message }}',
        ]);
    }
}
