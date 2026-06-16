<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;

interface AssetMapperInterface
{
    /**
     * Get the token address for a given token symbol on a specific chain.
     */
    public function getTokenAddress(string $tokenSymbol, CrossChainNetwork $chain): ?string;

    /**
     * Map a token address from one chain to its equivalent on another chain.
     */
    public function mapTokenAddress(
        string $tokenAddress,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
    ): ?string;

    /**
     * Get the canonical token symbol for a given address on a chain.
     */
    public function getCanonicalToken(string $tokenAddress, CrossChainNetwork $chain): ?string;

    /**
     * Get all supported tokens for a chain.
     *
     * @return array<string, string> Symbol => Address
     */
    public function getSupportedTokens(CrossChainNetwork $chain): array;
}
