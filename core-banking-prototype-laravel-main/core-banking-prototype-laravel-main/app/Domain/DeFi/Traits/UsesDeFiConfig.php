<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Traits;

use App\Domain\CrossChain\Enums\CrossChainNetwork;

/**
 * Shared DeFi configuration helpers for protocol connectors.
 *
 * Provides token address resolution and RPC URL lookup from the defi config namespace.
 */
trait UsesDeFiConfig
{
    /**
     * Resolve token symbol to contract address on a given chain.
     */
    private function resolveTokenAddress(string $token, CrossChainNetwork $chain): string
    {
        /** @var array<string, array<string, string>> $addresses */
        $addresses = (array) config('defi.token_addresses', []);
        $chainAddresses = $addresses[$chain->value] ?? [];

        return $chainAddresses[$token] ?? '0x' . str_repeat('0', 40);
    }

    /**
     * Get RPC URL for a given chain from config.
     */
    private function getRpcUrl(CrossChainNetwork $chain): ?string
    {
        $key = 'defi.rpc_urls.' . $chain->value;
        $url = config($key, '');

        return $url !== '' ? (string) $url : null;
    }
}
