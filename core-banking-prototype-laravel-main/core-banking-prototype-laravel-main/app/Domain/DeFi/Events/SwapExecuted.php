<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Events;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use Illuminate\Foundation\Events\Dispatchable;

class SwapExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly CrossChainNetwork $chain,
        public readonly DeFiProtocol $protocol,
        public readonly string $fromToken,
        public readonly string $toToken,
        public readonly string $inputAmount,
        public readonly string $outputAmount,
        public readonly string $walletAddress,
        public readonly string $txHash,
    ) {
    }
}
