<?php

declare(strict_types=1);

namespace App\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salary record for a staff member covering a single pay period.
 *
 * Captures base salary, structured allowances, bonuses, deductions,
 * overtime calculations, and the final gross/net figures for each period.
 *
 * @property string $id
 * @property string $user_id
 * @property string $period
 * @property int $base_salary
 * @property array<string,mixed> $allowances
 * @property string $overtime_hours
 * @property int $overtime_rate
 * @property array<string,mixed> $bonuses
 * @property array<string,mixed> $deductions
 * @property int $total_gross
 * @property int $total_net
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SalaryRecord extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'salary_records';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'user_id',
        'period',
        'base_salary',
        'allowances',
        'overtime_hours',
        'overtime_rate',
        'bonuses',
        'deductions',
        'total_gross',
        'total_net',
        'paid_at',
        'payment_method',
        'payment_reference',
        'notes',
        'created_by',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'allowances'     => 'array',
        'bonuses'        => 'array',
        'deductions'     => 'array',
        'paid_at'        => 'datetime',
        'overtime_hours' => 'decimal:2',
    ];

    /**
     * Get the staff member (user) this salary record belongs to.
     *
     * @return BelongsTo<User, SalaryRecord>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff member who created this salary record.
     *
     * @return BelongsTo<User, SalaryRecord>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
