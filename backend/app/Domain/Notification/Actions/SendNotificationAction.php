<?php

declare(strict_types=1);

namespace App\Domain\Notification\Actions;

use App\Domain\Notification\Data\SendNotificationDto;
use App\Domain\Notification\Models\NotificationLog;
use App\Domain\Notification\Services\NotificationService;

/**
 * Action to send a single notification using a template slug.
 *
 * Delegates rendering and delivery to NotificationService, and returns
 * the NotificationLog record that was created for audit purposes.
 */
class SendNotificationAction
{
    /**
     * Create a new SendNotificationAction.
     *
     * @param  NotificationService  $notificationService  Service handling template resolution and dispatch.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Execute the notification send.
     *
     * Resolves the template by slug, renders subject and body_html
     * with the provided variables, dispatches via the appropriate channel,
     * and logs the outcome.
     *
     * @param  SendNotificationDto  $dto  Validated notification payload.
     * @return NotificationLog            The created log record.
     *
     * @throws \Throwable  On any unexpected failure not handled by the service.
     */
    public function execute(SendNotificationDto $dto): NotificationLog
    {
        return $this->notificationService->send(
            slug:           $dto->templateSlug,
            recipient:      $dto->recipient,
            variables:      $dto->variables,
            notifiableType: $dto->notifiableType,
            notifiableId:   $dto->notifiableId,
        );
    }
}
