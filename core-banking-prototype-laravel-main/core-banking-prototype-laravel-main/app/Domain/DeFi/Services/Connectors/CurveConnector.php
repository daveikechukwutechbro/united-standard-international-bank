<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services\Connectors;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Curve Finance connector: stablecoin-optimized swaps.
 *
 * In production, integrates with Curve Registry and pool contracts.
 */
class CurveConnector implements SwapProtocolInterface
{
    private const SUPPORTED_CHAINS = ['ethereum', 'polygon', 'arbitrum'];

    private const STABLECOIN_TOKENS = ['USDC', 'USDT', 'DAI', 'FRAX'];

    public function getProtocol(): DeFiProtocol
    {
        return DeFiProtocol::CURVE;
    }

    public function getQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippageTolerance = 0.5,
    ): SwapQuote {
        // Curve excels at stablecoin swaps with very low fees
        $isStableSwap = $this->isStablecoinPair($fromToken, $toToken);
        $feeRate = $isStableSwap ? '0.0004' : '0.004'; // 0.04% vs 0.4%
        $fee = bcmul($amount, $feeRate, 8);
        $priceImpact = $isStableSwap ? '0.01' : '0.15';
        $outputAmount = bcsub($amount, $fee, 8);

        return new SwapQuote(
            quoteId: 'curve-' . Str::uuid()->toString(),
            chain: $chain,
            inputToken: $fromToken,
            outputToken: $toToken,
            inputAmount: $amount,
            outputAmount: $outputAmount,
            priceImpact: $priceImpact,
            protocol: DeFiProtocol::CURVE,
            gasEstimate: $this->estimateGas($chain),
            feeTier: null,
            expiresAt: CarbonImmutable::now()->addSeconds((int) config('defi.swap.quote_ttl_seconds', 60)),
        );
    }

    public function executeSwap(SwapQuote $quote, string $walletAddress): array
    {
        Log::info('Curve: Executing swap', [
            'chain' => $quote->chain->value,
            'pair'  => "{$quote->inputToken}/{$quote->outputToken}",
        ]);

        return [
            'tx_hash'       => '0x' . Str::random(64),
            'input_amount'  => $quote->inputAmount,
            'output_amount' => $quote->outputAmount,
            'price_impact'  => $quote->priceImpact,
        ];
    }

    public function getSupportedPairs(CrossChainNetwork $chain): array
    {
        if (! in_array($chain->value, self::SUPPORTED_CHAINS)) {
            return [];
        }

        $pairs = [];
        // Stablecoin pool
        foreach (self::STABLECOIN_TOKENS as $from) {
            foreach (self::STABLECOIN_TOKENS as $to) {
                if ($from !== $to) {
                    $pairs[] = ['from' => $from, 'to' => $to, 'fee_tier' => null];
                }
            }
        }

        // ETH/stETH pool
        $pairs[] = ['from' => 'WETH', 'to' => 'stETH', 'fee_tier' => null];
        $pairs[] = ['from' => 'stETH', 'to' => 'WETH', 'fee_tier' => null];

        return $pairs;
    }

    private function isStablecoinPair(string $fromToken, string $toToken): bool
    {
        return in_array($fromToken, self::STABLECOIN_TOKENS)
            && in_array($toToken, self::STABLECOIN_TOKENS);
    }

    private function estimateGas(CrossChainNetwork $chain): string
    {
        return match ($chain) {
            CrossChainNetwork::ETHEREUM => '12.00',
            CrossChainNetwork::POLYGON  => '0.03',
            CrossChainNetwork::ARBITRUM => '0.25',
            default                     => '5.00',
        };
    }
}
