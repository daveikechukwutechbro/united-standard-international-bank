<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Events;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Illuminate\Foundation\Events\Dispatchable;

class BridgeTransactionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly CrossChainNetwork $sourceChain,
        public readonly CrossChainNetwork $destChain,
        public readonly string $token,
        public readonly string $amount,
        public readonly BridgeProvider $provider,
        public readonly string $reason,
    ) {
    }
}
