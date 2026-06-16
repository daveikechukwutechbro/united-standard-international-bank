<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\ValueObjects;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Carbon\CarbonImmutable;

/**
 * Immutable value object representing a bridge transfer quote.
 */
final readonly class BridgeQuote
{
    public function __construct(
        public string $quoteId,
        public BridgeRoute $route,
        public string $inputAmount,
        public string $outputAmount,
        public string $fee,
        public string $feeCurrency,
        public int $estimatedTimeSeconds,
        public CarbonImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return CarbonImmutable::now()->greaterThan($this->expiresAt);
    }

    public function getProvider(): BridgeProvider
    {
        return $this->route->provider;
    }

    public function getSourceChain(): CrossChainNetwork
    {
        return $this->route->sourceChain;
    }

    public function getDestChain(): CrossChainNetwork
    {
        return $this->route->destChain;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quote_id'               => $this->quoteId,
            'route'                  => $this->route->toArray(),
            'input_amount'           => $this->inputAmount,
            'output_amount'          => $this->outputAmount,
            'fee'                    => $this->fee,
            'fee_currency'           => $this->feeCurrency,
            'estimated_time_seconds' => $this->estimatedTimeSeconds,
            'expires_at'             => $this->expiresAt->toIso8601String(),
        ];
    }
}
