<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->connector = new UniswapV3Connector(new AbiEncoder(), new EthRpcClient());
});

describe('UniswapV3Connector', function () {
    it('returns uniswap_v3 as protocol', function () {
        expect($this->connector->getProtocol())->toBe(DeFiProtocol::UNISWAP_V3);
    });

    it('generates quote with fee tier selection', function () {
        $quote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'WETH', '1000.00');

        expect($quote->protocol)->toBe(DeFiProtocol::UNISWAP_V3);
        expect($quote->feeTier)->not->toBeNull();
        expect(bccomp($quote->outputAmount, '0', 8))->toBe(1);
    });

    it('uses lowest fee tier for stablecoin pairs', function () {
        $quote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'DAI', '10000.00');

        expect($quote->feeTier)->toBe(100);
    });

    it('executes swap and returns tx hash', function () {
        $quote = $this->connector->getQuote(CrossChainNetwork::POLYGON, 'WETH', 'USDC', '5.00');
        $result = $this->connector->executeSwap($quote, '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['input_amount'])->toBe('5.00');
    });

    it('estimates lower gas for L2 chains', function () {
        $ethQuote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'WETH', '1000.00');
        $arbQuote = $this->connector->getQuote(CrossChainNetwork::ARBITRUM, 'USDC', 'WETH', '1000.00');

        expect(bccomp($ethQuote->gasEstimate, $arbQuote->gasEstimate, 2))->toBe(1);
    });

    it('returns supported pairs with multiple fee tiers', function () {
        $pairs = $this->connector->getSupportedPairs(CrossChainNetwork::ETHEREUM);

        expect($pairs)->not->toBeEmpty();
    });

    it('returns empty pairs for unsupported chains', function () {
        $pairs = $this->connector->getSupportedPairs(CrossChainNetwork::BITCOIN);

        expect($pairs)->toBeEmpty();
    });

    it('estimates higher price impact for larger amounts', function () {
        $smallQuote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'WETH', '100.00');
        $largeQuote = $this->connector->getQuote(CrossChainNetwork::ETHEREUM, 'USDC', 'WETH', '500000.00');

        expect(bccomp($largeQuote->priceImpact, $smallQuote->priceImpact, 4))->toBeGreaterThanOrEqual(0);
    });
});
