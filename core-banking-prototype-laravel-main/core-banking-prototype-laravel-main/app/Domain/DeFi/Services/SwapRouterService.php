<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Events\SwapExecuted;
use App\Domain\DeFi\Exceptions\InsufficientLiquidityException;
use App\Domain\DeFi\Exceptions\SlippageExceededException;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Routes swaps to the optimal connector based on pair, amount, and gas cost.
 */
class SwapRouterService
{
    public function __construct(
        private readonly SwapAggregatorService $aggregator,
    ) {
    }

    /**
     * Get the optimal swap route.
     *
     * @throws InsufficientLiquidityException
     */
    public function findBestRoute(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippage = 0.5,
    ): SwapQuote {
        return $this->aggregator->getBestQuote($chain, $fromToken, $toToken, $amount, $slippage);
    }

    /**
     * Execute a swap via the best route.
     *
     * @return array{tx_hash: string, input_amount: string, output_amount: string, price_impact: string, protocol: string}
     * @throws InsufficientLiquidityException
     * @throws SlippageExceededException
     */
    public function executeSwap(
        SwapQuote $quote,
        string $walletAddress,
    ): array {
        if ($quote->isExpired()) {
            throw new InsufficientLiquidityException('Swap quote has expired');
        }

        $maxPriceImpact = (float) config('defi.swap.max_price_impact', 3.0);
        if ((float) $quote->priceImpact > $maxPriceImpact) {
            throw SlippageExceededException::exceeded(
                (string) $maxPriceImpact,
                $quote->priceImpact,
                $maxPriceImpact,
            );
        }

        $connector = $this->getConnectorForProtocol($quote->protocol);
        $result = $connector->executeSwap($quote, $walletAddress);

        SwapExecuted::dispatch(
            $quote->chain,
            $quote->protocol,
            $quote->inputToken,
            $quote->outputToken,
            $quote->inputAmount,
            $result['output_amount'],
            $walletAddress,
            $result['tx_hash'],
        );

        Log::info('Swap executed', [
            'protocol' => $quote->protocol->value,
            'chain'    => $quote->chain->value,
            'pair'     => "{$quote->inputToken}/{$quote->outputToken}",
            'amount'   => $quote->inputAmount,
            'output'   => $result['output_amount'],
        ]);

        return array_merge($result, ['protocol' => $quote->protocol->value]);
    }

    private function getConnectorForProtocol(\App\Domain\DeFi\Enums\DeFiProtocol $protocol): SwapProtocolInterface
    {
        $connectors = $this->aggregator->getConnectors();

        if (! isset($connectors[$protocol->value])) {
            throw new RuntimeException("No swap connector registered for protocol: {$protocol->value}");
        }

        return $connectors[$protocol->value];
    }
}
