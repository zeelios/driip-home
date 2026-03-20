<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationLog;
use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;

/**
 * Service to send notifications through configured channels.
 *
 * Currently supports email (MVP). SMS and Zalo OA deferred to Phase 5.
 * All sends are logged to notification_logs for audit and debugging.
 */
class NotificationService
{
    /**
     * Send a notification using a template slug.
     *
     * Looks up the template, renders subject and body with the provided
     * variables, sends via the template's channel, and logs the outcome.
     *
     * @param  string              $slug            Template slug e.g. 'order_confirmed'
     * @param  string              $recipient       Email address or phone number.
     * @param  array<string,mixed> $variables       Template variable replacements.
     * @param  string|null         $notifiableType  Polymorphic type (e.g. Order::class)
     * @param  string|null         $notifiableId    Polymorphic ID
     * @return NotificationLog                      The created log record.
     */
    public function send(
        string  $slug,
        string  $recipient,
        array   $variables = [],
        ?string $notifiableType = null,
        ?string $notifiableId = null
    ): NotificationLog {
        $template = NotificationTemplate::findBySlug($slug);

        $log = NotificationLog::create([
            'channel'          => $template?->channel ?? 'email',
            'recipient'        => $recipient,
            'template_id'      => $template?->id,
            'subject'          => $template ? $this->render($template->subject ?? $slug, $variables) : $slug,
            'payload'          => $variables,
            'status'           => 'pending',
            'notifiable_type'  => $notifiableType,
            'notifiable_id'    => $notifiableId,
            'created_at'       => now(),
        ]);

        try {
            if ($template && $template->channel === 'email') {
                $this->sendEmail($recipient, $template, $variables);
            }

            $log->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => 1]);
        } catch (\Throwable $e) {
            $log->update([
                'status'    => 'failed',
                'failed_at' => now(),
                'attempts'  => 1,
                'error'     => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send an order-confirmed notification to the customer.
     *
     * Dispatches the 'order_confirmed' template with order context variables.
     *
     * @param  string  $recipient     Customer email address.
     * @param  string  $orderNumber   Human-readable order number.
     * @param  string  $customerName  Customer display name.
     * @param  int     $total         Order total in VND.
     * @param  string  $orderId       UUID of the order (used for polymorphic link).
     * @return NotificationLog
     */
    public function orderConfirmed(
        string $recipient,
        string $orderNumber,
        string $customerName,
        int    $total,
        string $orderId,
    ): NotificationLog {
        return $this->send(
            slug:           'order_confirmed',
            recipient:      $recipient,
            variables:      [
                'order_number'  => $orderNumber,
                'customer_name' => $customerName,
                'total'         => number_format($total),
            ],
            notifiableType: 'App\Domain\Order\Models\Order',
            notifiableId:   $orderId,
        );
    }

    /**
     * Send an order-shipped notification to the customer.
     *
     * Dispatches the 'order_shipped' template with tracking context variables.
     *
     * @param  string       $recipient       Customer email address.
     * @param  string       $orderNumber     Human-readable order number.
     * @param  string       $customerName    Customer display name.
     * @param  string|null  $trackingCode    Courier tracking code, if available.
     * @param  string       $orderId         UUID of the order (used for polymorphic link).
     * @return NotificationLog
     */
    public function orderShipped(
        string  $recipient,
        string  $orderNumber,
        string  $customerName,
        ?string $trackingCode,
        string  $orderId,
    ): NotificationLog {
        return $this->send(
            slug:           'order_shipped',
            recipient:      $recipient,
            variables:      [
                'order_number'  => $orderNumber,
                'customer_name' => $customerName,
                'tracking_code' => $trackingCode ?? '',
            ],
            notifiableType: 'App\Domain\Order\Models\Order',
            notifiableId:   $orderId,
        );
    }

    /**
     * Send an order-delivered notification to the customer.
     *
     * Dispatches the 'order_delivered' template to confirm successful delivery.
     *
     * @param  string  $recipient     Customer email address.
     * @param  string  $orderNumber   Human-readable order number.
     * @param  string  $customerName  Customer display name.
     * @param  string  $orderId       UUID of the order (used for polymorphic link).
     * @return NotificationLog
     */
    public function orderDelivered(
        string $recipient,
        string $orderNumber,
        string $customerName,
        string $orderId,
    ): NotificationLog {
        return $this->send(
            slug:           'order_delivered',
            recipient:      $recipient,
            variables:      [
                'order_number'  => $orderNumber,
                'customer_name' => $customerName,
            ],
            notifiableType: 'App\Domain\Order\Models\Order',
            notifiableId:   $orderId,
        );
    }

    /**
     * Replace template variables in a string.
     *
     * Substitutes all occurrences of {{ variable_name }} with the
     * corresponding value from the variables array.
     *
     * @param  string              $content    Template string containing placeholders.
     * @param  array<string,mixed> $variables  Key-value pairs of replacements.
     * @return string                          Rendered string with all placeholders replaced.
     */
    private function render(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{ {$key} }}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Send an email using Laravel Mail.
     *
     * Renders subject and body_html then sends via the configured mailer.
     *
     * @param  string               $recipient   Recipient email address.
     * @param  NotificationTemplate $template    Template containing subject and body_html.
     * @param  array<string,mixed>  $variables   Variable substitutions to apply.
     */
    private function sendEmail(string $recipient, NotificationTemplate $template, array $variables): void
    {
        $subject = $this->render($template->subject ?? '', $variables);
        $body    = $this->render($template->body_html, $variables);

        Mail::html($body, function ($message) use ($recipient, $subject) {
            $message->to($recipient)->subject($subject);
        });
    }
}
