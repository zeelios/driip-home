<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SaleEvent model representing a time-boxed promotional event (e.g. flash sale, drop launch).
 *
 * A sale event contains one or more SaleEventItems, each mapping a product variant
 * to a discounted sale price. When a sale event is activated via ActivateSaleEventAction,
 * all participating variants have their sale_price updated on the products table.
 * Ending the event via EndSaleEventAction reverses those overrides.
 *
 * @property string               $id
 * @property string               $name
 * @property string               $slug
 * @property string|null          $description
 * @property string               $type
 * @property string               $status
 * @property \Carbon\Carbon       $starts_at
 * @property \Carbon\Carbon|null  $ends_at
 * @property int|null             $max_orders_total
 * @property bool                 $is_public
 * @property string|null          $banner_url
 * @property string               $created_by
 * @property \Carbon\Carbon|null  $created_at
 * @property \Carbon\Carbon|null  $updated_at
 * @property \Carbon\Carbon|null  $deleted_at
 */
class SaleEvent extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'sale_events';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'max_orders_total',
        'is_public',
        'banner_url',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'is_public'  => 'boolean',
    ];

    /**
     * Get all items (variant-to-sale-price mappings) for this event.
     *
     * @return HasMany<SaleEventItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleEventItem::class, 'sale_event_id');
    }

    /**
     * Get the staff user who created this sale event.
     *
     * @return BelongsTo<User, SaleEvent>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine whether this sale event is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine whether this sale event is scheduled (not yet started).
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }
}
