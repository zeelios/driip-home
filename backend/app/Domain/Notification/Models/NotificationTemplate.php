<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * NotificationTemplate model for storing reusable notification templates.
 *
 * Templates are identified by a unique slug and support variable interpolation
 * using the {{ variable_name }} syntax. Currently supports email, with
 * SMS and Zalo OA channels planned for Phase 5.
 *
 * @property string            $id
 * @property string            $slug
 * @property string            $name
 * @property string            $channel
 * @property string|null       $subject
 * @property string            $body_html
 * @property array<int,string> $variables
 * @property string            $locale
 * @property bool              $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class NotificationTemplate extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'notification_templates';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'slug',
        'name',
        'channel',
        'subject',
        'body_html',
        'variables',
        'locale',
        'is_active',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Find a template by its slug.
     *
     * Returns null when no matching active template exists.
     *
     * @param  string  $slug  The unique template identifier (e.g. 'order_confirmed').
     * @return static|null
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}
