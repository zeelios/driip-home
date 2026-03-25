<?php

declare(strict_types=1);

namespace App\Domain\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log for bank checking operations.
 *
 * Records every attempt to check bank transactions,
 * including success/failure status and metrics.
 *
 * @property string                $id
 * @property string                $bank_config_id
 * @property string                $status
 * @property int                   $transactions_found
 * @property int                   $deposits_matched
 * @property string|null           $error_message
 * @property array<string,mixed>   $details
 * @property int|null              $duration_ms
 * @property \Carbon\Carbon         $started_at
 * @property \Carbon\Carbon|null   $completed_at
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class BankCheckLog extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'bank_check_logs';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'bank_config_id',
        'status',
        'transactions_found',
        'deposits_matched',
        'error_message',
        'details',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'transactions_found' => 'integer',
        'deposits_matched' => 'integer',
        'details' => 'array',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the bank config this log belongs to.
     *
     * @return BelongsTo<BankConfig, BankCheckLog>
     */
    public function bankConfig(): BelongsTo
    {
        return $this->belongsTo(BankConfig::class, 'bank_config_id');
    }

    /**
     * Mark the check as completed.
     *
     * @param  int   $transactionsFound
     * @param  int   $depositsMatched
     * @param  array<string,mixed>  $details
     * @return void
     */
    public function markCompleted(int $transactionsFound, int $depositsMatched, array $details = []): void
    {
        $this->update([
            'status' => 'success',
            'transactions_found' => $transactionsFound,
            'deposits_matched' => $depositsMatched,
            'details' => $details,
            'completed_at' => now(),
            'duration_ms' => $this->started_at->diffInMilliseconds(now()),
        ]);
    }

    /**
     * Mark the check as failed.
     *
     * @param  string  $errorMessage
     * @param  array<string,mixed>  $details
     * @return void
     */
    public function markFailed(string $errorMessage, array $details = []): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'details' => $details,
            'completed_at' => now(),
            'duration_ms' => $this->started_at->diffInMilliseconds(now()),
        ]);
    }
}
