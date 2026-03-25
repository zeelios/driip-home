<?php

declare(strict_types=1);

namespace App\Domain\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bank configuration for RPA crawling.
 *
 * Stores encrypted credentials and settings for automated
 * bank transaction checking.
 *
 * @property string                $id
 * @property string                $bank_provider
 * @property string                $account_number
 * @property string                $account_name
 * @property string                $credentials_encrypted
 * @property bool                  $is_active
 * @property \Carbon\Carbon|null   $last_check_at
 * @property int                   $check_interval_minutes
 * @property \Carbon\Carbon|null   $created_at
 * @property \Carbon\Carbon|null   $updated_at
 */
class BankConfig extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'bank_configs';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'bank_provider',
        'account_number',
        'account_name',
        'credentials_encrypted',
        'is_active',
        'last_check_at',
        'check_interval_minutes',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'is_active' => 'boolean',
        'last_check_at' => 'datetime',
        'check_interval_minutes' => 'integer',
    ];

    /**
     * Set the credentials (automatically encrypted).
     *
     * @param  array<string,string>  $credentials
     * @return void
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials_encrypted = encrypt(json_encode($credentials));
    }

    /**
     * Get the decrypted credentials.
     *
     * @return array<string,string>|null
     */
    public function getCredentials(): ?array
    {
        if (empty($this->credentials_encrypted)) {
            return null;
        }

        $decrypted = decrypt($this->credentials_encrypted);

        return json_decode($decrypted, true);
    }

    /**
     * Check if this bank config is due for a check.
     *
     * @return bool
     */
    public function isDueForCheck(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->last_check_at === null) {
            return true;
        }

        return $this->last_check_at->addMinutes($this->check_interval_minutes)->isPast();
    }

    /**
     * Mark as checked.
     *
     * @return void
     */
    public function markChecked(): void
    {
        $this->update(['last_check_at' => now()]);
    }
}
