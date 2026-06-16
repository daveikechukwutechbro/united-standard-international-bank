<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Illuminate\Support\Facades\Cache;

/**
 * Static + API-backed token address mapping across chains.
 * Extends the asset registry with caching and external API lookup.
 */
class CrossChainTokenMapService
{
    private const CACHE_PREFIX = 'token_map:';

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly CrossChainAssetRegistryService $assetRegistry,
    ) {
    }

    /**
     * Resolve a token address on a destination chain given source chain info.
     */
    public function resolveToken(
        string $tokenSymbolOrAddress,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
    ): ?string {
        // First try by symbol
        $destAddress = $this->assetRegistry->getTokenAddress($tokenSymbolOrAddress, $destChain);
        if ($destAddress !== null) {
            return $destAddress;
        }

        // Try mapping by address
        $destAddress = $this->assetRegistry->mapTokenAddress($tokenSymbolOrAddress, $sourceChain, $destChain);
        if ($destAddress !== null) {
            return $destAddress;
        }

        // Try cached external lookup
        return $this->getCachedExternalMapping($tokenSymbolOrAddress, $sourceChain, $destChain);
    }

    /**
     * Get token symbol regardless of whether input is symbol or address.
     */
    public function normalizeToSymbol(string $tokenSymbolOrAddress, CrossChainNetwork $chain): ?string
    {
        // If it looks like a symbol (no 0x prefix), return as-is if supported
        if (! str_starts_with($tokenSymbolOrAddress, '0x') && ! str_starts_with($tokenSymbolOrAddress, 'T')) {
            $address = $this->assetRegistry->getTokenAddress($tokenSymbolOrAddress, $chain);

            return $address !== null ? strtoupper($tokenSymbolOrAddress) : null;
        }

        // Look up the canonical symbol from address
        return $this->assetRegistry->getCanonicalToken($tokenSymbolOrAddress, $chain);
    }

    /**
     * Check if a token can be bridged between two chains.
     */
    public function canBridge(string $token, CrossChainNetwork $source, CrossChainNetwork $dest): bool
    {
        $sourceAddress = $this->assetRegistry->getTokenAddress($token, $source);
        $destAddress = $this->assetRegistry->getTokenAddress($token, $dest);

        return $sourceAddress !== null && $destAddress !== null;
    }

    /**
     * Get all bridgeable tokens between two chains.
     *
     * @return array<string> Token symbols
     */
    public function getBridgeableTokens(CrossChainNetwork $source, CrossChainNetwork $dest): array
    {
        $sourceTokens = $this->assetRegistry->getSupportedTokens($source);
        $destTokens = $this->assetRegistry->getSupportedTokens($dest);

        return array_values(array_intersect(
            array_keys($sourceTokens),
            array_keys($destTokens),
        ));
    }

    /**
     * Get comprehensive token info across chains.
     *
     * @return array<string, array<string, ?string>>
     */
    public function getTokenChainMap(string $tokenSymbol): array
    {
        $map = [];

        foreach (CrossChainNetwork::cases() as $chain) {
            $address = $this->assetRegistry->getTokenAddress($tokenSymbol, $chain);
            if ($address !== null) {
                $map[$chain->value] = $address;
            }
        }

        return [$tokenSymbol => $map];
    }

    private function getCachedExternalMapping(
        string $tokenAddress,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
    ): ?string {
        $cacheKey = self::CACHE_PREFIX . md5("{$tokenAddress}:{$sourceChain->value}:{$destChain->value}");

        return Cache::get($cacheKey);
    }
}
