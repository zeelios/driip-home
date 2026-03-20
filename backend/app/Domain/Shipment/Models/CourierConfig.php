<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Courier integration configuration.
 *
 * Stores API credentials, endpoint URLs, hub codes, and webhook secrets
 * for each courier partner. Sensitive fields (api_key, api_secret,
 * webhook_secret) are excluded from API resource output.
 *
 * @property string                    $id
 * @property string                    $courier_code
 * @property string                    $name
 * @property string|null               $api_endpoint
 * @property string|null               $api_key
 * @property string|null               $api_secret
 * @property string|null               $account_id
 * @property string|null               $pickup_hub_code
 * @property array<string,mixed>|null  $pickup_address
 * @property string|null               $webhook_secret
 * @property bool                      $is_active
 * @property array<string,mixed>       $settings
 * @property \Carbon\Carbon|null       $created_at
 * @property \Carbon\Carbon|null       $updated_at
 */
class CourierConfig extends Model
{
    use HasFactory, HasUuids;

    /** @var string The table associated with this model. */
    protected $table = 'courier_configs';

    /** @var list<string> The attributes that are mass-assignable. */
    protected $fillable = [
        'courier_code',
        'name',
        'api_endpoint',
        'api_key',
        'api_secret',
        'account_id',
        'pickup_hub_code',
        'pickup_address',
        'webhook_secret',
        'is_active',
        'settings',
    ];

    /** @var array<string,string> Attribute type casts. */
    protected $casts = [
        'pickup_address' => 'array',
        'settings'       => 'array',
        'is_active'      => 'boolean',
    ];
}
