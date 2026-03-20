<?php

declare(strict_types=1);

namespace App\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Staff user model representing an employee of Driip.
 *
 * @property string $id
 * @property string|null $employee_code
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $avatar
 * @property string|null $department
 * @property string|null $position
 * @property string $status
 * @property \Carbon\Carbon|null $hired_at
 * @property \Carbon\Carbon|null $terminated_at
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /** @var string The table associated with this model. */
    protected $table = 'users';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'employee_code', 'name', 'email', 'phone', 'password',
        'avatar', 'department', 'position', 'status',
        'hired_at', 'terminated_at', 'notes',
    ];

    /** @var list<string> Attributes hidden from arrays/JSON. */
    protected $hidden = ['password', 'remember_token'];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'hired_at'          => 'date',
        'terminated_at'     => 'date',
        'password'          => 'hashed',
    ];

    /**
     * Get this user's extended staff profile.
     *
     * @return HasOne<StaffProfile>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(StaffProfile::class, 'user_id');
    }

    /**
     * Get salary records for this staff member.
     *
     * @return HasMany<SalaryRecord>
     */
    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class, 'user_id');
    }

    /**
     * Check if the staff member is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
