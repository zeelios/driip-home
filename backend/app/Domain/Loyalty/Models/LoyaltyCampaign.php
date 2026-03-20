<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A promotional loyalty campaign that grants bonus or multiplied points.
 *
 * Campaigns can apply a multiplier to points earned, or a flat bonus, during
 * a defined time window. Optional conditions allow restricting the campaign to
 * specific products, categories or customer tiers.
 *
 * @property string                $id
 * @property string                $name
 * @property string                $type
 * @property string                $multiplier
 * @property int                   $bonus_points
 * @property array<string,mixed>   $conditions
 * @property \Carbon\Carbon|null   $starts_at
 * @property \Carbon\Carbon|null   $ends_at
 * @property bool                  $is_active
 * @property string|null           $created_by
 */
class LoyaltyCampaign extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'loyalty_campaigns';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'type',
        'multiplier',
        'bonus_points',
        'conditions',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'conditions'  => 'array',
        'multiplier'  => 'decimal:2',
        'is_active'   => 'boolean',
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'bonus_points' => 'integer',
    ];
}
