<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Key-value configuration setting stored in the database.
 *
 * Settings are grouped by a logical namespace (e.g. "loyalty", "tax") and
 * each entry has a typed value that is automatically cast when retrieved
 * via the static helpers.
 *
 * @property string      $id
 * @property string      $group
 * @property string      $key
 * @property string|null $value
 * @property string      $type
 * @property string|null $label
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class Setting extends Model
{
    use HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'settings';

    /**
     * Disable automatic timestamp management; only updated_at is tracked.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'updated_at',
        'updated_by',
    ];

    /**
     * Retrieve a setting value cast to its declared type.
     *
     * Returns $default if no matching setting row is found.
     *
     * @param  string  $group    The setting group namespace.
     * @param  string  $key      The setting key within the group.
     * @param  mixed   $default  Value to return when the setting is missing.
     * @return mixed
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = static::where('group', $group)->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Persist a new value for the given setting key.
     *
     * @param  string  $group  The setting group namespace.
     * @param  string  $key    The setting key within the group.
     * @param  mixed   $value  The new value to store (will be cast to string).
     * @return void
     */
    public static function set(string $group, string $key, mixed $value): void
    {
        static::where('group', $group)
            ->where('key', $key)
            ->update([
                'value'      => (string) $value,
                'updated_at' => now()->toDateTimeString(),
            ]);
    }

    /**
     * Cast a raw string value to the appropriate PHP type based on its declared type.
     *
     * @param  string|null  $value  The raw stored string value.
     * @param  string       $type   The declared type (string, integer, float, boolean, json).
     * @return mixed
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'float'   => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
