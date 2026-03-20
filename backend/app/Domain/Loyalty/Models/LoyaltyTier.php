<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Loyalty tier definition (e.g. Silver, Gold, Platinum).
 *
 * Tiers are earned by accumulating lifetime points. Each tier grants
 * a set of perks such as discount percentage, free shipping and early access.
 *
 * @property string               $id
 * @property string               $name
 * @property string               $slug
 * @property int                  $min_lifetime_points
 * @property string               $discount_percent
 * @property bool                 $free_shipping
 * @property bool                 $early_access
 * @property string               $birthday_multiplier
 * @property array<int,string>    $perks
 * @property string|null          $color
 * @property int                  $sort_order
 */
class LoyaltyTier extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'loyalty_tiers';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'name',
        'slug',
        'min_lifetime_points',
        'discount_percent',
        'free_shipping',
        'early_access',
        'birthday_multiplier',
        'perks',
        'color',
        'sort_order',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'perks'               => 'array',
        'free_shipping'       => 'boolean',
        'early_access'        => 'boolean',
        'birthday_multiplier' => 'decimal:2',
        'discount_percent'    => 'decimal:2',
        'min_lifetime_points' => 'integer',
        'sort_order'          => 'integer',
    ];

    /**
     * Get all loyalty accounts currently at this tier.
     *
     * @return HasMany<LoyaltyAccount>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(LoyaltyAccount::class, 'tier_id');
    }
}
