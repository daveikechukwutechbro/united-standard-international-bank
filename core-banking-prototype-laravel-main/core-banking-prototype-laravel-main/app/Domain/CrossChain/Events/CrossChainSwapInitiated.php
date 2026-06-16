<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Events;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Illuminate\Foundation\Events\Dispatchable;

class CrossChainSwapInitiated
{
    use Dispatchable;

    public function __construct(
        public readonly CrossChainNetwork $sourceChain,
        public readonly CrossChainNetwork $destChain,
        public readonly string $inputToken,
        public readonly string $outputToken,
        public readonly string $amount,
        public readonly string $walletAddress,
        public readonly string $quoteId,
    ) {
    }
}
