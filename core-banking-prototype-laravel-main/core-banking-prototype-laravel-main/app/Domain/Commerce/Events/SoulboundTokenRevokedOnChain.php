<?php

declare(strict_types=1);

namespace App\Domain\Commerce\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a soulbound token is revoked (burned) on-chain.
 */
class SoulboundTokenRevokedOnChain
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $tokenId,
        public readonly int $onChainTokenId,
        public readonly string $contractAddress,
        public readonly string $txHash,
        public readonly string $network,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token_id'          => $this->tokenId,
            'on_chain_token_id' => $this->onChainTokenId,
            'contract_address'  => $this->contractAddress,
            'tx_hash'           => $this->txHash,
            'network'           => $this->network,
        ];
    }
}
