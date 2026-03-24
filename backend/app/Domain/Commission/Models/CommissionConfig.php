<?php

declare(strict_types=1);

namespace App\Domain\Commission\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CommissionConfig model representing staff commission rate configurations.
 *
 * Allows per-staff commission rates with date ranges and category-specific
 * overrides for flexible commission structures.
 *
 * @property string               $id
 * @property string               $staff_id
 * @property float                $rate_percent
 * @property array<string,float>  $category_rates
 * @property string               $effective_from
 * @property string|null          $effective_to
 * @property bool                 $is_active
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 */
class CommissionConfig extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'commission_configs';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'staff_id',
        'rate_percent',
        'category_rates',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'rate_percent'   => 'float',
        'category_rates' => 'array',
        'is_active'      => 'boolean',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /**
     * Get the staff member this config belongs to.
     *
     * @return BelongsTo<User, CommissionConfig>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Scope: active configurations for today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<CommissionConfig>  $query
     * @return \Illuminate\Database\Eloquent\Builder<CommissionConfig>
     */
    public function scopeActiveToday($query)
    {
        return $query
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now()->toDateString());
            });
    }
}
