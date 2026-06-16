<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores webhook endpoint metadata for Alchemy/Helius providers.
 *
 * @property int $id
 * @property string $provider
 * @property string $network
 * @property int $shard
 * @property string $external_webhook_id
 * @property string $signing_key
 * @property string $webhook_url
 * @property bool $is_active
 * @property int $address_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookEndpoint extends Model
{
    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'provider',
        'network',
        'shard',
        'external_webhook_id',
        'signing_key',
        'webhook_url',
        'is_active',
        'address_count',
    ];

    protected $casts = [
        'signing_key'   => 'encrypted',
        'is_active'     => 'boolean',
        'shard'         => 'integer',
        'address_count' => 'integer',
    ];

    /** Maximum addresses per webhook (Alchemy limit) */
    public const MAX_ADDRESSES_PER_WEBHOOK = 100_000;

    public function hasCapacity(): bool
    {
        return $this->address_count < self::MAX_ADDRESSES_PER_WEBHOOK;
    }

    public function incrementAddressCount(): void
    {
        $this->increment('address_count');
    }

    public function decrementAddressCount(): void
    {
        if ($this->address_count > 0) {
            $this->decrement('address_count');
        }
    }
}
