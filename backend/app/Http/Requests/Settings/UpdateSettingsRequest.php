<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for a batch settings update.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for a batch settings update.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'settings'          => ['required', 'array', 'min:1'],
            'settings.*.group'  => ['required', 'string', 'max:100'],
            'settings.*.key'    => ['required', 'string', 'max:100'],
            'settings.*.value'  => ['present'],
        ];
    }

    /**
     * Build an UpdateSettingsDto from the validated request data.
     *
     * @return \App\Domain\Settings\Data\UpdateSettingsDto
     */
    public function dto(): \App\Domain\Settings\Data\UpdateSettingsDto
    {
        return new \App\Domain\Settings\Data\UpdateSettingsDto(
            settings:  $this->input('settings', []),
            updatedBy: $this->user()?->id,
        );
    }
}
