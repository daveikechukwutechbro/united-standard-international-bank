<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use Throwable;

/**
 * Queries all bridge adapters and returns cheapest/fastest route options.
 */
class BridgeFeeComparisonService
{
    public function __construct(
        private readonly BridgeOrchestratorService $orchestrator,
    ) {
    }

    /**
     * Compare fees across all providers for a given route.
     *
     * @return array{quotes: array<BridgeQuote>, cheapest: ?BridgeQuote, fastest: ?BridgeQuote, summary: array<string, mixed>}
     */
    public function compare(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        try {
            $quotes = $this->orchestrator->getQuotes($sourceChain, $destChain, $token, $amount);
        } catch (Throwable) {
            return [
                'quotes'   => [],
                'cheapest' => null,
                'fastest'  => null,
                'summary'  => ['available_providers' => 0, 'route_supported' => false],
            ];
        }

        if (empty($quotes)) {
            return [
                'quotes'   => [],
                'cheapest' => null,
                'fastest'  => null,
                'summary'  => ['available_providers' => 0, 'route_supported' => true],
            ];
        }

        $sortedByFee = $quotes;
        usort($sortedByFee, fn (BridgeQuote $a, BridgeQuote $b) => bccomp($a->fee, $b->fee, 18));

        $sortedByTime = $quotes;
        usort($sortedByTime, fn (BridgeQuote $a, BridgeQuote $b) => $a->estimatedTimeSeconds <=> $b->estimatedTimeSeconds);

        $cheapest = $sortedByFee[0];
        $fastest = $sortedByTime[0];

        return [
            'quotes'   => $quotes,
            'cheapest' => $cheapest,
            'fastest'  => $fastest,
            'summary'  => [
                'available_providers' => count($quotes),
                'route_supported'     => true,
                'fee_range'           => [
                    'min' => $cheapest->fee,
                    'max' => end($sortedByFee)->fee,
                ],
                'time_range' => [
                    'min_seconds' => $fastest->estimatedTimeSeconds,
                    'max_seconds' => end($sortedByTime)->estimatedTimeSeconds,
                ],
                'best_value'       => $cheapest->getProvider()->value,
                'fastest_provider' => $fastest->getProvider()->value,
            ],
        ];
    }

    /**
     * Get a ranked list of quotes sorted by an overall score (fee weight + time weight).
     *
     * @return array<BridgeQuote>
     */
    public function getRankedQuotes(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
        float $feeWeight = 0.6,
        float $timeWeight = 0.4,
    ): array {
        $comparison = $this->compare($sourceChain, $destChain, $token, $amount);
        $quotes = $comparison['quotes'];

        if (count($quotes) <= 1) {
            return $quotes;
        }

        // Normalize and score
        $maxFee = '0';
        $maxTime = 0;
        foreach ($quotes as $quote) {
            if (bccomp($quote->fee, $maxFee, 18) > 0) {
                $maxFee = $quote->fee;
            }
            if ($quote->estimatedTimeSeconds > $maxTime) {
                $maxTime = $quote->estimatedTimeSeconds;
            }
        }

        $scored = [];
        foreach ($quotes as $i => $quote) {
            $feeScore = $maxFee !== '0' ? 1.0 - (float) bcdiv($quote->fee, $maxFee, 8) : 0;
            $timeScore = $maxTime > 0 ? 1.0 - ($quote->estimatedTimeSeconds / $maxTime) : 0;
            $scored[$i] = ($feeWeight * $feeScore) + ($timeWeight * $timeScore);
        }

        arsort($scored);
        $ranked = [];
        foreach (array_keys($scored) as $idx) {
            $ranked[] = $quotes[$idx];
        }

        return $ranked;
    }
}
