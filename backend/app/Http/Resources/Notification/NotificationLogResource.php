<?php

declare(strict_types=1);

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for NotificationLog.
 *
 * @mixin \App\Domain\Notification\Models\NotificationLog
 */
class NotificationLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'template_id' => $this->template_id,
            'subject' => $this->subject,
            'payload' => $this->payload,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'sent_at' => $this->sent_at,
            'failed_at' => $this->failed_at,
            'error' => $this->error,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'created_at' => $this->created_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
