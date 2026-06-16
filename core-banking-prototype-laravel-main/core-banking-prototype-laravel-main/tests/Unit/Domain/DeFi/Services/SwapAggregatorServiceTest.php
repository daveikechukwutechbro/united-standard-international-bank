<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Exceptions\InsufficientLiquidityException;
use App\Domain\DeFi\Services\SwapAggregatorService;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Carbon\CarbonImmutable;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new SwapAggregatorService();
});

function createMockSwapConnector(
    DeFiProtocol $protocol,
    string $outputAmount = '990.00',
    string $gasEstimate = '5.00',
): SwapProtocolInterface&Mockery\MockInterface {
    $mock = Mockery::mock(SwapProtocolInterface::class);
    $mock->shouldReceive('getProtocol')->andReturn($protocol);
    $mock->shouldReceive('getQuote')->andReturn(new SwapQuote(
        quoteId: 'q-' . uniqid(),
        chain: CrossChainNetwork::ETHEREUM,
        inputToken: 'USDC',
        outputToken: 'WETH',
        inputAmount: '1000.00',
        outputAmount: $outputAmount,
        priceImpact: '0.1',
        protocol: $protocol,
        gasEstimate: $gasEstimate,
        feeTier: 3000,
        expiresAt: CarbonImmutable::now()->addMinutes(1),
    ));

    return $mock;
}

describe('SwapAggregatorService', function () {
    it('registers and retrieves connectors', function () {
        $connector = createMockSwapConnector(DeFiProtocol::DEMO);
        $this->service->registerConnector($connector);

        expect($this->service->getConnectors())->toHaveKey('demo');
    });

    it('gets quotes from all connectors', function () {
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::DEMO, '990.00'));
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::UNISWAP_V3, '995.00'));

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quotes)->toHaveCount(2);
    });

    it('throws when no connectors provide quotes', function () {
        $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            'UNKNOWN',
            'WETH',
            '1000.00',
        );
    })->throws(InsufficientLiquidityException::class);

    it('gets best quote by highest output', function () {
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::DEMO, '990.00'));
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::UNISWAP_V3, '998.00'));

        $best = $this->service->getBestQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($best->outputAmount)->toBe('998.00');
    });

    it('gets cheapest gas quote', function () {
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::DEMO, '990.00', '10.00'));
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::CURVE, '988.00', '2.00'));

        $cheapest = $this->service->getCheapestGasQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($cheapest->gasEstimate)->toBe('2.00');
    });

    it('gracefully handles failing connectors', function () {
        $failingConnector = Mockery::mock(SwapProtocolInterface::class);
        $failingConnector->shouldReceive('getProtocol')->andReturn(DeFiProtocol::UNISWAP_V3);
        $failingConnector->shouldReceive('getQuote')->andThrow(new RuntimeException('API error'));

        $workingConnector = createMockSwapConnector(DeFiProtocol::DEMO);

        $this->service->registerConnector($failingConnector);
        $this->service->registerConnector($workingConnector);

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quotes)->toHaveCount(1);
    });

    it('handles custom slippage tolerance', function () {
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::DEMO));

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
            1.0,
        );

        expect($quotes)->toHaveCount(1);
    });

    it('returns quotes from multiple protocols', function () {
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::DEMO));
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::UNISWAP_V3));
        $this->service->registerConnector(createMockSwapConnector(DeFiProtocol::CURVE));

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quotes)->toHaveCount(3);
    });
});
