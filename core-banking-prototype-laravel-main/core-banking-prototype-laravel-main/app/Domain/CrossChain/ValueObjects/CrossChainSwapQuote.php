<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\ValueObjects;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\ValueObjects\SwapQuote;

/**
 * Immutable value object representing a cross-chain swap (bridge + swap).
 */
final readonly class CrossChainSwapQuote
{
    public function __construct(
        public string $quoteId,
        public CrossChainNetwork $sourceChain,
        public CrossChainNetwork $destChain,
        public string $inputToken,
        public string $outputToken,
        public string $inputAmount,
        public string $estimatedOutputAmount,
        public BridgeQuote $bridgeQuote,
        public ?SwapQuote $swapQuote,
        public string $totalFee,
        public string $feeCurrency,
        public int $estimatedTimeSeconds,
    ) {
    }

    public function requiresSwap(): bool
    {
        return $this->swapQuote !== null;
    }

    public function getTotalPriceImpact(): string
    {
        if ($this->swapQuote === null) {
            return '0.00';
        }

        return $this->swapQuote->priceImpact;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quote_id'                => $this->quoteId,
            'source_chain'            => $this->sourceChain->value,
            'dest_chain'              => $this->destChain->value,
            'input_token'             => $this->inputToken,
            'output_token'            => $this->outputToken,
            'input_amount'            => $this->inputAmount,
            'estimated_output_amount' => $this->estimatedOutputAmount,
            'bridge_quote'            => $this->bridgeQuote->toArray(),
            'swap_quote'              => $this->swapQuote?->toArray(),
            'total_fee'               => $this->totalFee,
            'fee_currency'            => $this->feeCurrency,
            'estimated_time_seconds'  => $this->estimatedTimeSeconds,
        ];
    }
}
