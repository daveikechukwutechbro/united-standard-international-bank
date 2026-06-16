<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\ValueObjects;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\CrossChainNetwork;

/**
 * Immutable value object representing a bridge route between chains.
 */
final readonly class BridgeRoute
{
    public function __construct(
        public CrossChainNetwork $sourceChain,
        public CrossChainNetwork $destChain,
        public string $token,
        public BridgeProvider $provider,
        public int $estimatedTimeSeconds,
        public string $baseFee,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_chain'           => $this->sourceChain->value,
            'dest_chain'             => $this->destChain->value,
            'token'                  => $this->token,
            'provider'               => $this->provider->value,
            'estimated_time_seconds' => $this->estimatedTimeSeconds,
            'base_fee'               => $this->baseFee,
        ];
    }
}
