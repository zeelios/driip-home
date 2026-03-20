<?php

declare(strict_types=1);

namespace App\Domain\Tax\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tax rate configuration for a defined effective period.
 *
 * Multiple TaxConfig records can exist but only one should be active at any
 * given time. The static activeRate() helper finds the applicable rate for today.
 *
 * @property string               $id
 * @property string               $name
 * @property string               $rate
 * @property string|null          $applies_to
 * @property array<int,string>    $applies_to_ids
 * @property \Carbon\Carbon|null  $effective_from
 * @property \Carbon\Carbon|null  $effective_to
 * @property bool                 $is_active
 * @property \Carbon\Carbon       $created_at
 */
class TaxConfig extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'tax_configs';

    /**
     * Disable automatic timestamp management; only created_at is stored.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'rate',
        'applies_to',
        'applies_to_ids',
        'effective_from',
        'effective_to',
        'is_active',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'rate'           => 'decimal:2',
        'applies_to_ids' => 'array',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
        'created_at'     => 'datetime',
    ];

    /**
     * Return the tax rate of the currently active configuration.
     *
     * Looks for an active record whose effective_from is on or before today
     * and whose effective_to is either null (open-ended) or on/after today.
     * Returns 0.0 if no matching configuration is found.
     *
     * @return float
     */
    public static function activeRate(): float
    {
        $today = Carbon::today();

        $config = static::where('is_active', true)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->first();

        return $config !== null ? (float) $config->rate : 0.0;
    }
}
