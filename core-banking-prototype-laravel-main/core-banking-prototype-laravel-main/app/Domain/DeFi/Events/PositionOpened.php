<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Events;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use Illuminate\Foundation\Events\Dispatchable;

class PositionOpened
{
    use Dispatchable;

    public function __construct(
        public readonly string $positionId,
        public readonly DeFiProtocol $protocol,
        public readonly DeFiPositionType $type,
        public readonly CrossChainNetwork $chain,
        public readonly string $asset,
        public readonly string $amount,
        public readonly string $walletAddress,
    ) {
    }
}
