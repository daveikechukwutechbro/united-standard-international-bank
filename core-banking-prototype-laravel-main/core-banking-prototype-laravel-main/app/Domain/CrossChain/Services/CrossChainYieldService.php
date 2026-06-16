<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\LendingProtocolInterface;
use App\Domain\DeFi\Contracts\LiquidStakingInterface;
use App\Domain\DeFi\Services\DeFiPortfolioService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds best yield opportunities across chains and suggests optimal bridging.
 */
class CrossChainYieldService
{
    /** @var array<CrossChainNetwork> */
    private array $supportedChains = [
        CrossChainNetwork::ETHEREUM,
        CrossChainNetwork::POLYGON,
        CrossChainNetwork::ARBITRUM,
        CrossChainNetwork::OPTIMISM,
        CrossChainNetwork::BASE,
    ];

    public function __construct(
        private readonly DeFiPortfolioService $portfolioService,
        private readonly BridgeOrchestratorService $bridgeOrchestrator,
        private readonly LendingProtocolInterface $lendingProtocol,
        private readonly LiquidStakingInterface $stakingProtocol,
    ) {
    }

    /**
     * Find the best yield opportunities across all supported chains.
     *
     * @return array<array{chain: string, protocol: string, type: string, token: string, apy: string}>
     */
    public function findBestYieldAcrossChains(string $token = 'USDC'): array
    {
        $opportunities = [];

        foreach ($this->supportedChains as $chain) {
            $markets = $this->lendingProtocol->getMarkets($chain);

            foreach ($markets as $market) {
                if ($market['token'] === $token || $token === '*') {
                    $opportunities[] = [
                        'chain'    => $chain->value,
                        'protocol' => 'aave_v3',
                        'type'     => 'lending',
                        'token'    => $market['token'],
                        'apy'      => $market['supply_apy'],
                    ];
                }
            }

            // Include staking opportunities for ETH
            if ($token === 'ETH' || $token === '*') {
                $stakingApy = $this->stakingProtocol->getStakingAPY($chain);
                $opportunities[] = [
                    'chain'    => $chain->value,
                    'protocol' => 'lido',
                    'type'     => 'staking',
                    'token'    => 'ETH',
                    'apy'      => $stakingApy,
                ];
            }
        }

        // Sort by APY descending
        usort($opportunities, function (array $a, array $b) {
            return bccomp($b['apy'], $a['apy'], 8);
        });

        return $opportunities;
    }

    /**
     * Get the optimal chain to deploy capital for a given token.
     *
     * @return array{chain: string, apy: string, protocol: string, bridge_fee: ?string}
     */
    public function getOptimalChainForYield(
        CrossChainNetwork $currentChain,
        string $token,
        string $amount,
    ): array {
        $opportunities = $this->findBestYieldAcrossChains($token);

        if (empty($opportunities)) {
            return [
                'chain'      => $currentChain->value,
                'apy'        => '0.00',
                'protocol'   => 'none',
                'bridge_fee' => null,
            ];
        }

        $best = $opportunities[0];
        $bestChain = CrossChainNetwork::from($best['chain']);
        $bridgeFee = null;

        // Calculate bridge cost if different chain
        if ($bestChain !== $currentChain) {
            try {
                $bridgeQuote = $this->bridgeOrchestrator->getBestQuote(
                    $currentChain,
                    $bestChain,
                    $token,
                    $amount,
                );
                $bridgeFee = $bridgeQuote->fee;
            } catch (Throwable $e) {
                Log::warning('CrossChainYield: Could not get bridge quote', [
                    'from'  => $currentChain->value,
                    'to'    => $bestChain->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'chain'      => $best['chain'],
            'apy'        => $best['apy'],
            'protocol'   => $best['protocol'],
            'bridge_fee' => $bridgeFee,
        ];
    }

    /**
     * Get a yield comparison across all chains for a given token.
     *
     * @return array<array{chain: string, apy: string, protocol: string, bridge_fee: ?string, net_apy_estimate: string}>
     */
    public function getYieldComparison(
        CrossChainNetwork $currentChain,
        string $token,
        string $amount,
    ): array {
        $opportunities = $this->findBestYieldAcrossChains($token);
        $comparison = [];

        foreach ($opportunities as $opportunity) {
            $oppChain = CrossChainNetwork::from($opportunity['chain']);
            $bridgeFee = null;
            $netApy = $opportunity['apy'];

            if ($oppChain !== $currentChain) {
                try {
                    $bridgeQuote = $this->bridgeOrchestrator->getBestQuote(
                        $currentChain,
                        $oppChain,
                        $token,
                        $amount,
                    );
                    $bridgeFee = $bridgeQuote->fee;

                    // Estimate net APY: subtract bridge cost as percentage of amount
                    if (bccomp($amount, '0', 8) > 0) {
                        $bridgeCostPct = bcmul(bcdiv($bridgeFee, $amount, 8), '100', 4);
                        $netApy = bcsub($opportunity['apy'], $bridgeCostPct, 4);
                    }
                } catch (Throwable) {
                    // Bridge not available for this route
                    continue;
                }
            }

            $comparison[] = [
                'chain'            => $opportunity['chain'],
                'apy'              => $opportunity['apy'],
                'protocol'         => $opportunity['protocol'],
                'bridge_fee'       => $bridgeFee,
                'net_apy_estimate' => $netApy,
            ];
        }

        return $comparison;
    }
}
