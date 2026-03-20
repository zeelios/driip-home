<?php

declare(strict_types=1);

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single notification template.
 *
 * @mixin \App\Domain\Notification\Models\NotificationTemplate
 */
class NotificationTemplateResource extends JsonResource
{
    /**
     * Transform the notification template into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'channel'    => $this->channel,
            'subject'    => $this->subject,
            'body_html'  => $this->body_html,
            'variables'  => $this->variables,
            'locale'     => $this->locale,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
