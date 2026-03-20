<?php

declare(strict_types=1);

namespace App\Http\Resources\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single application setting.
 *
 * @mixin \App\Domain\Settings\Models\Setting
 */
class SettingResource extends JsonResource
{
    /**
     * Transform the setting into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'group'      => $this->group,
            'key'        => $this->key,
            'value'      => $this->value,
            'type'       => $this->type,
            'label'      => $this->label,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ];
    }
}
