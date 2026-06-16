<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\AxelarBridgeAdapter;
use App\Domain\CrossChain\Services\Adapters\DemoBridgeAdapter;
use App\Domain\CrossChain\Services\Adapters\LayerZeroBridgeAdapter;
use App\Domain\CrossChain\Services\Adapters\WormholeBridgeAdapter;
use App\Domain\CrossChain\Services\BridgeFeeComparisonService;
use App\Domain\CrossChain\Services\BridgeOrchestratorService;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $encoder = new AbiEncoder();
    $rpcClient = new EthRpcClient();

    $this->orchestrator = new BridgeOrchestratorService();
    $this->orchestrator->registerAdapter(new DemoBridgeAdapter());
    $this->orchestrator->registerAdapter(new WormholeBridgeAdapter($encoder, $rpcClient));
    $this->orchestrator->registerAdapter(new LayerZeroBridgeAdapter());
    $this->orchestrator->registerAdapter(new AxelarBridgeAdapter());
    $this->service = new BridgeFeeComparisonService($this->orchestrator);
});

describe('BridgeFeeComparisonService', function () {
    it('compares fees across all providers', function () {
        $result = $this->service->compare(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($result['quotes'])->not->toBeEmpty();
        expect($result['cheapest'])->not->toBeNull();
        expect($result['fastest'])->not->toBeNull();
        expect($result['summary']['available_providers'])->toBeGreaterThan(1);
    });

    it('identifies cheapest provider', function () {
        $result = $this->service->compare(
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            '500.00',
        );

        $cheapest = $result['cheapest'];
        foreach ($result['quotes'] as $quote) {
            expect(bccomp($cheapest->fee, $quote->fee, 18))->toBeLessThanOrEqual(0);
        }
    });

    it('identifies fastest provider', function () {
        $result = $this->service->compare(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'WETH',
            '2.00',
        );

        $fastest = $result['fastest'];
        foreach ($result['quotes'] as $quote) {
            expect($fastest->estimatedTimeSeconds)->toBeLessThanOrEqual($quote->estimatedTimeSeconds);
        }
    });

    it('returns empty result for unsupported routes', function () {
        $result = $this->service->compare(
            CrossChainNetwork::BITCOIN,
            CrossChainNetwork::TRON,
            'BTC',
            '1.0',
        );

        expect($result['quotes'])->toBeEmpty();
        expect($result['cheapest'])->toBeNull();
        expect($result['fastest'])->toBeNull();
    });

    it('includes fee and time ranges in summary', function () {
        $result = $this->service->compare(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($result['summary'])->toHaveKey('fee_range');
        expect($result['summary'])->toHaveKey('time_range');
        expect($result['summary']['fee_range'])->toHaveKey('min');
        expect($result['summary']['fee_range'])->toHaveKey('max');
    });

    it('ranks quotes by weighted score', function () {
        $ranked = $this->service->getRankedQuotes(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($ranked)->not->toBeEmpty();
        expect(count($ranked))->toBeGreaterThan(1);
    });

    it('supports custom fee/time weights for ranking', function () {
        $feeOptimized = $this->service->getRankedQuotes(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
            feeWeight: 1.0,
            timeWeight: 0.0,
        );

        $timeOptimized = $this->service->getRankedQuotes(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
            feeWeight: 0.0,
            timeWeight: 1.0,
        );

        expect($feeOptimized)->not->toBeEmpty();
        expect($timeOptimized)->not->toBeEmpty();
    });

    it('handles single provider routes', function () {
        // Solana only supported by Wormhole
        $result = $this->service->compare(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::SOLANA,
            'USDC',
            '1000.00',
        );

        expect($result['summary']['available_providers'])->toBe(1);
    });
});
