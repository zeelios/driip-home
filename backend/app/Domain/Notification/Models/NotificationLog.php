<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationLog model recording every notification send attempt.
 *
 * Provides an audit trail for all outgoing notifications, capturing the
 * recipient, payload, delivery status, and any error messages.
 * This model does not use updated_at — entries are immutable after creation.
 *
 * @property string                    $id
 * @property string                    $channel
 * @property string                    $recipient
 * @property string|null               $template_id
 * @property string|null               $subject
 * @property array<string,mixed>       $payload
 * @property string                    $status
 * @property int                       $attempts
 * @property \Carbon\Carbon|null       $sent_at
 * @property \Carbon\Carbon|null       $failed_at
 * @property string|null               $error
 * @property string|null               $notifiable_type
 * @property string|null               $notifiable_id
 * @property \Carbon\Carbon|null       $created_at
 */
class NotificationLog extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'notification_logs';

    /**
     * Disable automatic timestamp management.
     *
     * Only created_at is stored; updated_at does not exist on this table.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var string|null The name of the created_at column. */
    const CREATED_AT = 'created_at';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'channel',
        'recipient',
        'template_id',
        'subject',
        'payload',
        'status',
        'attempts',
        'sent_at',
        'failed_at',
        'error',
        'notifiable_type',
        'notifiable_id',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'payload'    => 'array',
        'sent_at'    => 'datetime',
        'failed_at'  => 'datetime',
        'created_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    /**
     * Get the notification template used for this log entry.
     *
     * Returns null when the template has been deleted or was not used.
     *
     * @return BelongsTo<NotificationTemplate, NotificationLog>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }
}
