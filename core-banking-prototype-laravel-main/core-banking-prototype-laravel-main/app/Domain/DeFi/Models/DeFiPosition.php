<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Models;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionStatus;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeFiPosition extends Model
{
    use HasUuids;

    protected $table = 'defi_positions';

    protected $fillable = [
        'user_id',
        'protocol',
        'type',
        'status',
        'chain',
        'asset',
        'amount',
        'value_usd',
        'apy',
        'health_factor',
        'metadata',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'protocol'      => DeFiProtocol::class,
        'type'          => DeFiPositionType::class,
        'status'        => DeFiPositionStatus::class,
        'chain'         => CrossChainNetwork::class,
        'amount'        => 'decimal:18',
        'value_usd'     => 'decimal:2',
        'apy'           => 'decimal:4',
        'health_factor' => 'decimal:4',
        'metadata'      => 'array',
        'opened_at'     => 'datetime',
        'closed_at'     => 'datetime',
    ];

    /** @return BelongsTo<\App\Models\User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
