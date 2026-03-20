<?php

declare(strict_types=1);

namespace App\Domain\Notification\Data;

/**
 * Data Transfer Object for sending a notification via a template.
 *
 * Carries the minimal fields required to locate a template, populate
 * its variables, and deliver the message to the recipient.
 */
readonly class SendNotificationDto
{
    /**
     * Create a new SendNotificationDto.
     *
     * @param  string              $templateSlug   Unique slug identifying the notification template.
     * @param  string              $recipient      Email address or phone number of the recipient.
     * @param  array<string,mixed> $variables      Variable substitutions for the template body.
     * @param  string|null         $notifiableType Polymorphic model class for context (e.g. Order::class).
     * @param  string|null         $notifiableId   Polymorphic model UUID for context.
     */
    public function __construct(
        public string  $templateSlug,
        public string  $recipient,
        public array   $variables = [],
        public ?string $notifiableType = null,
        public ?string $notifiableId = null,
    ) {}

    /**
     * Build from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            templateSlug:   (string) $data['template_slug'],
            recipient:      (string) $data['recipient'],
            variables:      (array) ($data['variables'] ?? []),
            notifiableType: isset($data['notifiable_type']) ? (string) $data['notifiable_type'] : null,
            notifiableId:   isset($data['notifiable_id']) ? (string) $data['notifiable_id'] : null,
        );
    }
}
