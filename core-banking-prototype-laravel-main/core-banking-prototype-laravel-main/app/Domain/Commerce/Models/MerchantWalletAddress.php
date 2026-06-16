<?php

declare(strict_types=1);

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merchant wallet address per network.
 *
 * @property string $id
 * @property string $merchant_id
 * @property string $network
 * @property string $wallet_address
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MerchantWalletAddress extends Model
{
    use HasUuids;

    protected $table = 'merchant_wallet_addresses';

    protected $fillable = [
        'merchant_id',
        'network',
        'wallet_address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
