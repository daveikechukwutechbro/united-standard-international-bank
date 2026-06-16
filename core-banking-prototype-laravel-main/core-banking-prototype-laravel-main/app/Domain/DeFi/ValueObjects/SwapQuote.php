<?php

declare(strict_types=1);

namespace App\Domain\DeFi\ValueObjects;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use Carbon\CarbonImmutable;

final readonly class SwapQuote
{
    public function __construct(
        public string $quoteId,
        public CrossChainNetwork $chain,
        public string $inputToken,
        public string $outputToken,
        public string $inputAmount,
        public string $outputAmount,
        public string $priceImpact,
        public DeFiProtocol $protocol,
        public string $gasEstimate,
        public ?int $feeTier,
        public CarbonImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return CarbonImmutable::now()->greaterThan($this->expiresAt);
    }

    public function getEffectiveRate(): string
    {
        if (empty($this->inputAmount) || bccomp($this->inputAmount, '0', 18) === 0) {
            return '0';
        }

        return bcdiv($this->outputAmount, $this->inputAmount, 18);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quote_id'      => $this->quoteId,
            'chain'         => $this->chain->value,
            'input_token'   => $this->inputToken,
            'output_token'  => $this->outputToken,
            'input_amount'  => $this->inputAmount,
            'output_amount' => $this->outputAmount,
            'price_impact'  => $this->priceImpact,
            'protocol'      => $this->protocol->value,
            'gas_estimate'  => $this->gasEstimate,
            'fee_tier'      => $this->feeTier,
            'expires_at'    => $this->expiresAt->toIso8601String(),
        ];
    }
}
