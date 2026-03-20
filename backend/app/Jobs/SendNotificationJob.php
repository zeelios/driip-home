<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Notification\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued job to send a single notification via a template slug.
 *
 * Delegates all send logic to NotificationService, which handles
 * template resolution, rendering, delivery, and log creation.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new SendNotificationJob.
     *
     * @param  string              $slug            Template slug (e.g. 'order_confirmed').
     * @param  string              $recipient       Email address or phone number.
     * @param  array<string,mixed> $variables       Variable substitutions for the template.
     * @param  string|null         $notifiableType  Polymorphic model class (e.g. Order::class).
     * @param  string|null         $notifiableId    Polymorphic model UUID.
     */
    public function __construct(
        private readonly string  $slug,
        private readonly string  $recipient,
        private readonly array   $variables = [],
        private readonly ?string $notifiableType = null,
        private readonly ?string $notifiableId = null,
    ) {}

    /**
     * Execute the job.
     *
     * @param  NotificationService  $service
     */
    public function handle(NotificationService $service): void
    {
        $service->send(
            $this->slug,
            $this->recipient,
            $this->variables,
            $this->notifiableType,
            $this->notifiableId,
        );
    }
}
