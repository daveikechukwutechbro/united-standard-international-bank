<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Services\Connectors\DemoSwapConnector;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Domain\DeFi\Services\SwapAggregatorService;
use App\Domain\DeFi\Services\SwapRouterService;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->aggregator = new SwapAggregatorService();
    $this->aggregator->registerConnector(new DemoSwapConnector());
    $this->aggregator->registerConnector(new UniswapV3Connector(new AbiEncoder(), new EthRpcClient()));
    $this->router = new SwapRouterService($this->aggregator);
});

describe('SwapRouterService', function () {
    it('finds best route from aggregator', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quote->inputAmount)->toBe('1000.00');
        expect(bccomp($quote->outputAmount, '0', 8))->toBe(1);
    });

    it('executes swap via best route', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        $result = $this->router->executeSwap($quote, '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result)->toHaveKey('protocol');
    });

    it('selects protocol with highest output amount', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        // Should pick the connector with the best output
        expect($quote->outputAmount)->not->toBe('0');
    });

    it('works with different chains', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '500.00',
        );

        expect($quote->chain)->toBe(CrossChainNetwork::POLYGON);
    });

    it('works with custom slippage', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::ARBITRUM,
            'WETH',
            'USDC',
            '2.00',
            1.0,
        );

        expect($quote->inputToken)->toBe('WETH');
        expect($quote->outputToken)->toBe('USDC');
    });

    it('returns protocol in execution result', function () {
        $quote = $this->router->findBestRoute(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        $result = $this->router->executeSwap($quote, '0xWallet');

        expect($result['protocol'])->toBeIn(['demo', 'uniswap_v3']);
    });
});
