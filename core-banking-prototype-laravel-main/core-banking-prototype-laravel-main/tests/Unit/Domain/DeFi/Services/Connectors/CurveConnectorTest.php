<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\CurveConnector;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->connector = new CurveConnector();
});

describe('CurveConnector', function () {
    it('returns curve as protocol', function () {
        expect($this->connector->getProtocol())->toBe(DeFiProtocol::CURVE);
    });

    it('offers better rates for stablecoin pairs', function () {
        $stableQuote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'DAI', '10000.00');
        $mixedQuote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'WETH', '10000.00');

        // Stablecoin swap should yield more output (lower fee)
        expect(bccomp($stableQuote->outputAmount, $mixedQuote->outputAmount, 8))->toBe(1);
    });

    it('has lower price impact for stablecoin swaps', function () {
        $quote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'DAI', '10000.00');

        expect((float) $quote->priceImpact)->toBeLessThanOrEqual(0.05);
    });

    it('executes swap successfully', function () {
        $quote = $this->connector->getQuote(CrossChainNetwork::POLYGON, 'USDC', 'USDT', '5000.00');
        $result = $this->connector->executeSwap($quote, '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['output_amount'])->toBe($quote->outputAmount);
    });

    it('includes stablecoin and ETH/stETH pairs', function () {
        $pairs = $this->connector->getSupportedPairs(CrossChainNetwork::ETHEREUM);
        $pairKeys = array_map(fn ($p) => "{$p['from']}/{$p['to']}", $pairs);

        expect($pairKeys)->toContain('USDC/DAI');
        expect($pairKeys)->toContain('WETH/stETH');
    });

    it('returns empty pairs for unsupported chains', function () {
        $pairs = $this->connector->getSupportedPairs(CrossChainNetwork::BSC);

        expect($pairs)->toBeEmpty();
    });
});
