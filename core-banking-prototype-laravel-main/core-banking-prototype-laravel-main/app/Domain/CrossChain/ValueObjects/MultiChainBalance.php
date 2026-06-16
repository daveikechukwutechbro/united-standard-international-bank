<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\ValueObjects;

use App\Domain\CrossChain\Enums\CrossChainNetwork;

/**
 * Immutable value object representing a token balance on a specific chain.
 */
final readonly class MultiChainBalance
{
    public function __construct(
        public CrossChainNetwork $chain,
        public string $token,
        public string $balance,
        public string $valueUsd,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chain'     => $this->chain->value,
            'token'     => $this->token,
            'balance'   => $this->balance,
            'value_usd' => $this->valueUsd,
        ];
    }
}
