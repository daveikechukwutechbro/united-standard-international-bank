<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Carbon\CarbonImmutable;

uses(Tests\TestCase::class);

describe('SwapQuote', function () {
    it('returns zero rate for zero input amount', function () {
        $quote = new SwapQuote(
            quoteId: 'q-1',
            chain: CrossChainNetwork::ETHEREUM,
            inputToken: 'USDC',
            outputToken: 'WETH',
            inputAmount: '0',
            outputAmount: '0',
            priceImpact: '0',
            protocol: DeFiProtocol::DEMO,
            gasEstimate: '0.001',
            feeTier: null,
            expiresAt: CarbonImmutable::now()->addMinutes(5),
        );

        expect($quote->getEffectiveRate())->toBe('0');
    });

    it('returns zero rate for empty string input amount', function () {
        $quote = new SwapQuote(
            quoteId: 'q-2',
            chain: CrossChainNetwork::ETHEREUM,
            inputToken: 'USDC',
            outputToken: 'WETH',
            inputAmount: '',
            outputAmount: '0',
            priceImpact: '0',
            protocol: DeFiProtocol::DEMO,
            gasEstimate: '0.001',
            feeTier: null,
            expiresAt: CarbonImmutable::now()->addMinutes(5),
        );

        expect($quote->getEffectiveRate())->toBe('0');
    });

    it('calculates effective rate correctly', function () {
        $quote = new SwapQuote(
            quoteId: 'q-3',
            chain: CrossChainNetwork::ETHEREUM,
            inputToken: 'USDC',
            outputToken: 'WETH',
            inputAmount: '1000',
            outputAmount: '500',
            priceImpact: '0.1',
            protocol: DeFiProtocol::DEMO,
            gasEstimate: '0.001',
            feeTier: null,
            expiresAt: CarbonImmutable::now()->addMinutes(5),
        );

        expect($quote->getEffectiveRate())->toBe('0.500000000000000000');
    });
});
