<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\MultiChainBalance;
use App\Domain\DeFi\Services\DeFiPortfolioService;

/**
 * Aggregated portfolio view across all chains: balances, DeFi positions, bridge history.
 */
class MultiChainPortfolioService
{
    /** @var array<CrossChainNetwork> */
    private array $supportedChains = [
        CrossChainNetwork::ETHEREUM,
        CrossChainNetwork::POLYGON,
        CrossChainNetwork::ARBITRUM,
        CrossChainNetwork::OPTIMISM,
        CrossChainNetwork::BASE,
        CrossChainNetwork::BSC,
    ];

    public function __construct(
        private readonly DeFiPortfolioService $defiPortfolio,
        private readonly BridgeTransactionTracker $bridgeTracker,
        private readonly CrossChainAssetRegistryService $assetRegistry,
    ) {
    }

    /**
     * Get a complete multi-chain portfolio for a wallet address.
     *
     * @return array{
     *     wallet_address: string,
     *     total_value_usd: string,
     *     balances: array<array{chain: string, token: string, balance: string, value_usd: string}>,
     *     defi_summary: array<string, mixed>,
     *     bridge_history: array{total: int, pending: int, completed: int},
     *     chains_active: array<string>,
     * }
     */
    public function getFullPortfolio(string $walletAddress): array
    {
        $balances = $this->getBalancesAcrossChains($walletAddress);
        $defiSummary = $this->defiPortfolio->getPortfolioSummary($walletAddress);
        $bridgeHistory = $this->getBridgeHistorySummary($walletAddress);

        $totalValue = '0.00';
        $chainsActive = [];

        foreach ($balances as $balance) {
            $totalValue = bcadd($totalValue, $balance->valueUsd, 2);

            if (bccomp($balance->balance, '0', 8) > 0 && ! in_array($balance->chain->value, $chainsActive)) {
                $chainsActive[] = $balance->chain->value;
            }
        }

        // Add DeFi positions value
        $totalValue = bcadd($totalValue, $defiSummary['total_value_usd'], 2);

        return [
            'wallet_address'  => $walletAddress,
            'total_value_usd' => $totalValue,
            'balances'        => array_map(fn (MultiChainBalance $b) => $b->toArray(), $balances),
            'defi_summary'    => $defiSummary,
            'bridge_history'  => $bridgeHistory,
            'chains_active'   => $chainsActive,
        ];
    }

    /**
     * Get token balances across all supported chains.
     *
     * @return array<MultiChainBalance>
     */
    public function getBalancesAcrossChains(string $walletAddress): array
    {
        $balances = [];

        foreach ($this->supportedChains as $chain) {
            $tokens = $this->assetRegistry->getSupportedTokens($chain);

            foreach (array_keys($tokens) as $token) {
                // In production: query on-chain balance via RPC
                // For now: return simulated balances
                $balance = $this->getSimulatedBalance($chain, $token, $walletAddress);

                if ($balance !== null) {
                    $balances[] = $balance;
                }
            }
        }

        return $balances;
    }

    /**
     * Get portfolio value broken down by chain.
     *
     * @return array<string, array{chain: string, total_value_usd: string, token_count: int}>
     */
    public function getValueByChain(string $walletAddress): array
    {
        $balances = $this->getBalancesAcrossChains($walletAddress);
        $byChain = [];

        foreach ($balances as $balance) {
            $chain = $balance->chain->value;

            if (! isset($byChain[$chain])) {
                $byChain[$chain] = [
                    'chain'           => $chain,
                    'total_value_usd' => '0.00',
                    'token_count'     => 0,
                ];
            }

            $byChain[$chain]['total_value_usd'] = bcadd(
                $byChain[$chain]['total_value_usd'],
                $balance->valueUsd,
                2,
            );
            $byChain[$chain]['token_count']++;
        }

        return $byChain;
    }

    /**
     * Get bridge transaction history summary.
     *
     * @return array{total: int, pending: int, completed: int}
     */
    public function getBridgeHistorySummary(string $walletAddress): array
    {
        $transactions = $this->bridgeTracker->getUserTransactions($walletAddress);
        $pending = $this->bridgeTracker->getPendingTransactions($walletAddress);

        return [
            'total'     => count($transactions),
            'pending'   => count($pending),
            'completed' => count($transactions) - count($pending),
        ];
    }

    /**
     * Simulate balance for a token on a chain.
     * In production: replaced by actual RPC calls to chain nodes.
     */
    private function getSimulatedBalance(
        CrossChainNetwork $chain,
        string $token,
        string $walletAddress,
    ): ?MultiChainBalance {
        // Demo balances: simulate holdings on primary chains
        $demoBalances = [
            'ethereum' => ['USDC' => '5000.00', 'WETH' => '2.50', 'DAI' => '3000.00'],
            'polygon'  => ['USDC' => '2000.00', 'WETH' => '1.00'],
            'arbitrum' => ['USDC' => '1500.00', 'WETH' => '0.50'],
            'optimism' => ['USDC' => '500.00'],
            'base'     => ['USDC' => '800.00'],
            'bsc'      => ['USDC' => '300.00'],
        ];

        $chainBalances = $demoBalances[$chain->value] ?? [];
        $balance = $chainBalances[$token] ?? null;

        if ($balance === null) {
            return null;
        }

        // Simplified USD pricing
        $prices = [
            'USDC' => '1.00',
            'USDT' => '1.00',
            'DAI'  => '1.00',
            'WETH' => '2500.00',
            'WBTC' => '45000.00',
            'ETH'  => '2500.00',
        ];

        $price = $prices[$token] ?? '1.00';
        $valueUsd = bcmul($balance, $price, 2);

        return new MultiChainBalance(
            chain: $chain,
            token: $token,
            balance: $balance,
            valueUsd: $valueUsd,
        );
    }
}
