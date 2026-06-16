<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Models;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BridgeTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'source_chain',
        'dest_chain',
        'token',
        'amount',
        'provider',
        'status',
        'sender_address',
        'recipient_address',
        'source_tx_hash',
        'dest_tx_hash',
        'fee_amount',
        'fee_currency',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'source_chain' => CrossChainNetwork::class,
        'dest_chain'   => CrossChainNetwork::class,
        'provider'     => BridgeProvider::class,
        'status'       => BridgeStatus::class,
        'amount'       => 'decimal:18',
        'fee_amount'   => 'decimal:18',
        'metadata'     => 'array',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<\App\Models\User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
