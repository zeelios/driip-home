<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Domain\Settings\Data\UpdateSettingsDto;
use App\Domain\Settings\Models\Setting;

/**
 * Action: batch-update multiple application settings.
 *
 * Iterates over the provided settings array and updates each setting's
 * value using the Setting::set() helper. The updated_by field is recorded
 * for each updated setting.
 */
class UpdateSettingsAction
{
    /**
     * Execute the batch settings update.
     *
     * @param  UpdateSettingsDto  $dto  The validated batch settings data.
     *
     * @return void
     */
    public function execute(UpdateSettingsDto $dto): void
    {
        foreach ($dto->settings as $settingData) {
            Setting::where('group', $settingData['group'])
                ->where('key', $settingData['key'])
                ->update([
                    'value'      => (string) $settingData['value'],
                    'updated_at' => now()->toDateTimeString(),
                    'updated_by' => $dto->updatedBy,
                ]);
        }
    }
}
