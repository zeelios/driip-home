<?php

declare(strict_types=1);

namespace App\Domain\Customer\Models;

use App\Domain\Staff\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A logged interaction between staff and a customer.
 *
 * Immutable append-only log. Each record captures the channel, summary,
 * outcome and an optional follow-up timestamp. There is no updated_at because
 * interaction records must not be modified after creation.
 *
 * @property string                $id
 * @property string                $customer_id
 * @property string                $type
 * @property string|null           $channel
 * @property string|null           $summary
 * @property string|null           $outcome
 * @property \Carbon\Carbon|null   $follow_up_at
 * @property string|null           $created_by
 * @property \Carbon\Carbon        $created_at
 */
class CustomerInteraction extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'customer_interactions';

    /**
     * Disable automatic timestamp management; we manage created_at manually.
     *
     * @var bool
     */
    public $timestamps = false;

    /** @var string The name of the created-at column. */
    const CREATED_AT = 'created_at';

    /** @var null No updated_at column; records are immutable. */
    const UPDATED_AT = null;

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'customer_id',
        'type',
        'channel',
        'summary',
        'outcome',
        'follow_up_at',
        'created_by',
        'created_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'follow_up_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    /**
     * Get the customer this interaction belongs to.
     *
     * @return BelongsTo<Customer, CustomerInteraction>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the staff member who created this interaction record.
     *
     * @return BelongsTo<User, CustomerInteraction>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
