<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services;

use App\Domain\DeFi\ValueObjects\DeFiPosition;

/**
 * Aggregated DeFi portfolio view: total value, protocol breakdown, yield earned, health factors.
 */
class DeFiPortfolioService
{
    public function __construct(
        private readonly DeFiPositionTrackerService $positionTracker,
    ) {
    }

    /**
     * Get full portfolio summary for a wallet.
     *
     * @return array<string, mixed>
     */
    public function getPortfolioSummary(string $walletAddress): array
    {
        $positions = $this->positionTracker->getActivePositions($walletAddress);

        return [
            'total_value_usd'    => $this->calculateTotalValue($positions),
            'positions_count'    => count($positions),
            'protocol_breakdown' => $this->getProtocolBreakdown($positions),
            'chain_breakdown'    => $this->getChainBreakdown($positions),
            'type_breakdown'     => $this->getTypeBreakdown($positions),
            'weighted_avg_apy'   => $this->calculateWeightedApy($positions),
            'at_risk_positions'  => count($this->positionTracker->getAtRiskPositions($walletAddress)),
        ];
    }

    /**
     * Get portfolio by chain.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getByChain(string $walletAddress): array
    {
        $positions = $this->positionTracker->getActivePositions($walletAddress);
        $byChain = [];

        foreach ($positions as $position) {
            $chain = $position->chain->value;
            if (! isset($byChain[$chain])) {
                $byChain[$chain] = [
                    'chain'     => $chain,
                    'value_usd' => '0',
                    'positions' => 0,
                    'avg_apy'   => '0',
                ];
            }

            $byChain[$chain]['value_usd'] = bcadd($byChain[$chain]['value_usd'], $position->valueUsd, 2);
            $byChain[$chain]['positions']++;
        }

        return $byChain;
    }

    /**
     * Get yield opportunities (positions sorted by APY).
     *
     * @return array<array<string, mixed>>
     */
    public function getYieldOpportunities(string $walletAddress): array
    {
        $positions = $this->positionTracker->getActivePositions($walletAddress);

        usort($positions, fn (DeFiPosition $a, DeFiPosition $b) => bccomp($b->apy, $a->apy, 4));

        return array_map(fn (DeFiPosition $pos) => $pos->toArray(), $positions);
    }

    /**
     * @param array<DeFiPosition> $positions
     */
    private function calculateTotalValue(array $positions): string
    {
        $total = '0';
        foreach ($positions as $position) {
            $total = bcadd($total, $position->valueUsd, 2);
        }

        return $total;
    }

    /**
     * @param array<DeFiPosition> $positions
     * @return array<string, array{value_usd: string, positions: int}>
     */
    private function getProtocolBreakdown(array $positions): array
    {
        return $this->buildBreakdown($positions, fn (DeFiPosition $pos) => $pos->protocol->value);
    }

    /**
     * @param array<DeFiPosition> $positions
     * @return array<string, array{value_usd: string, positions: int}>
     */
    private function getChainBreakdown(array $positions): array
    {
        return $this->buildBreakdown($positions, fn (DeFiPosition $pos) => $pos->chain->value);
    }

    /**
     * @param array<DeFiPosition> $positions
     * @return array<string, array{value_usd: string, positions: int}>
     */
    private function getTypeBreakdown(array $positions): array
    {
        return $this->buildBreakdown($positions, fn (DeFiPosition $pos) => $pos->type->value);
    }

    /**
     * @param array<DeFiPosition> $positions
     * @return array<string, array{value_usd: string, positions: int}>
     */
    private function buildBreakdown(array $positions, callable $keyExtractor): array
    {
        $breakdown = [];
        foreach ($positions as $pos) {
            $key = $keyExtractor($pos);
            if (! isset($breakdown[$key])) {
                $breakdown[$key] = ['value_usd' => '0', 'positions' => 0];
            }
            $breakdown[$key]['value_usd'] = bcadd($breakdown[$key]['value_usd'], $pos->valueUsd, 2);
            $breakdown[$key]['positions']++;
        }

        return $breakdown;
    }

    /**
     * @param array<DeFiPosition> $positions
     */
    private function calculateWeightedApy(array $positions): string
    {
        $totalValue = $this->calculateTotalValue($positions);
        if ($totalValue === '0' || empty($positions)) {
            return '0';
        }

        $weightedSum = '0';
        foreach ($positions as $pos) {
            $weight = bcmul($pos->apy, $pos->valueUsd, 8);
            $weightedSum = bcadd($weightedSum, $weight, 8);
        }

        return bcdiv($weightedSum, $totalValue, 4);
    }
}
