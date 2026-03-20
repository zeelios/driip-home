<?php

declare(strict_types=1);

namespace App\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended staff profile containing personal and financial details.
 *
 * Stores identity documents, date of birth, address information,
 * bank account data, and emergency contact details for a staff member.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $id_card_number
 * @property \Carbon\Carbon|null $id_card_issued_at
 * @property string|null $id_card_issued_by
 * @property \Carbon\Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $address
 * @property string|null $province
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class StaffProfile extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'staff_profiles';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'user_id',
        'id_card_number',
        'id_card_issued_at',
        'id_card_issued_by',
        'date_of_birth',
        'gender',
        'address',
        'province',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'id_card_issued_at' => 'date',
        'date_of_birth'     => 'date',
    ];

    /**
     * Get the staff member (user) who owns this profile.
     *
     * @return BelongsTo<User, StaffProfile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
