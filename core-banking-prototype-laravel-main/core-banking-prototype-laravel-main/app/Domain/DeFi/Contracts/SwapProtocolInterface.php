<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\ValueObjects\SwapQuote;

interface SwapProtocolInterface
{
    public function getProtocol(): DeFiProtocol;

    /**
     * Get a quote for a token swap.
     */
    public function getQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippageTolerance = 0.5,
    ): SwapQuote;

    /**
     * Execute a swap based on a quote.
     *
     * @return array{tx_hash: string, input_amount: string, output_amount: string, price_impact: string}
     */
    public function executeSwap(SwapQuote $quote, string $walletAddress): array;

    /**
     * Get supported trading pairs on a given chain.
     *
     * @return array<array{from: string, to: string, fee_tier: ?int}>
     */
    public function getSupportedPairs(CrossChainNetwork $chain): array;
}
