<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Exceptions\InsufficientLiquidityException;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Aggregates quotes from multiple DEXs and finds the best route.
 */
class SwapAggregatorService
{
    /** @var array<string, SwapProtocolInterface> */
    private array $connectors = [];

    public function registerConnector(SwapProtocolInterface $connector): void
    {
        $this->connectors[$connector->getProtocol()->value] = $connector;
    }

    /**
     * @return array<string, SwapProtocolInterface>
     */
    public function getConnectors(): array
    {
        return $this->connectors;
    }

    /**
     * Get quotes from all available DEXs for a swap.
     *
     * @return array<SwapQuote>
     * @throws InsufficientLiquidityException
     */
    public function getQuotes(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippage = 0.5,
    ): array {
        $quotes = [];

        foreach ($this->connectors as $connector) {
            try {
                $quotes[] = $connector->getQuote($chain, $fromToken, $toToken, $amount, $slippage);
            } catch (Throwable $e) {
                Log::warning('DeFi connector quote failed', [
                    'protocol' => $connector->getProtocol()->value,
                    'chain'    => $chain->value,
                    'pair'     => "{$fromToken}/{$toToken}",
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if (empty($quotes)) {
            throw InsufficientLiquidityException::forPair($fromToken, $toToken, $amount);
        }

        return $quotes;
    }

    /**
     * Get the best swap quote (highest output amount).
     *
     * @throws InsufficientLiquidityException
     */
    public function getBestQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippage = 0.5,
    ): SwapQuote {
        $quotes = $this->getQuotes($chain, $fromToken, $toToken, $amount, $slippage);

        usort($quotes, fn (SwapQuote $a, SwapQuote $b) => bccomp($b->outputAmount, $a->outputAmount, 18));

        return $quotes[0];
    }

    /**
     * Get the cheapest swap quote (lowest gas cost).
     *
     * @throws InsufficientLiquidityException
     */
    public function getCheapestGasQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippage = 0.5,
    ): SwapQuote {
        $quotes = $this->getQuotes($chain, $fromToken, $toToken, $amount, $slippage);

        usort($quotes, fn (SwapQuote $a, SwapQuote $b) => bccomp($a->gasEstimate, $b->gasEstimate, 18));

        return $quotes[0];
    }
}
