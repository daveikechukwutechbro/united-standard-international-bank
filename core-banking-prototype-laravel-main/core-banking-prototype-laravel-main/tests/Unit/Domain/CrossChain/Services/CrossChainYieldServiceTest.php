<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\DemoBridgeAdapter;
use App\Domain\CrossChain\Services\BridgeOrchestratorService;
use App\Domain\CrossChain\Services\CrossChainYieldService;
use App\Domain\DeFi\Services\Connectors\AaveV3Connector;
use App\Domain\DeFi\Services\Connectors\LidoConnector;
use App\Domain\DeFi\Services\DeFiPortfolioService;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $positionTracker = new DeFiPositionTrackerService();
    $portfolioService = new DeFiPortfolioService($positionTracker);

    $bridgeOrchestrator = new BridgeOrchestratorService();
    $bridgeOrchestrator->registerAdapter(new DemoBridgeAdapter());

    $encoder = new AbiEncoder();
    $rpcClient = new EthRpcClient();

    $this->service = new CrossChainYieldService(
        $portfolioService,
        $bridgeOrchestrator,
        new AaveV3Connector($encoder, $rpcClient),
        new LidoConnector(),
    );
});

describe('CrossChainYieldService', function () {
    it('finds best yield across chains for a token', function () {
        $opportunities = $this->service->findBestYieldAcrossChains('USDC');

        expect($opportunities)->not->toBeEmpty();

        // Should be sorted by APY descending
        for ($i = 1; $i < count($opportunities); $i++) {
            expect(bccomp($opportunities[$i - 1]['apy'], $opportunities[$i]['apy'], 8))->toBeGreaterThanOrEqual(0);
        }
    });

    it('includes staking opportunities for ETH', function () {
        $opportunities = $this->service->findBestYieldAcrossChains('ETH');

        $stakingOpps = array_filter($opportunities, fn ($o) => $o['type'] === 'staking');
        expect($stakingOpps)->not->toBeEmpty();
    });

    it('returns all opportunities with wildcard token', function () {
        $opportunities = $this->service->findBestYieldAcrossChains('*');

        expect(count($opportunities))->toBeGreaterThan(5);
    });

    it('gets optimal chain for yield', function () {
        $optimal = $this->service->getOptimalChainForYield(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '10000.00',
        );

        expect($optimal)->toHaveKeys(['chain', 'apy', 'protocol']);
        expect((float) $optimal['apy'])->toBeGreaterThan(0);
    });

    it('includes bridge fee when optimal chain differs from current', function () {
        $optimal = $this->service->getOptimalChainForYield(
            CrossChainNetwork::BSC,
            'USDC',
            '10000.00',
        );

        // Aave V3 supports ethereum/polygon/arbitrum/optimism/base but not BSC
        // So optimal chain should differ from BSC, requiring a bridge
        if ($optimal['chain'] !== CrossChainNetwork::BSC->value) {
            expect($optimal['bridge_fee'])->not->toBeNull();
        }
    });

    it('returns yield comparison across chains', function () {
        $comparison = $this->service->getYieldComparison(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '5000.00',
        );

        expect($comparison)->not->toBeEmpty();
        expect($comparison[0])->toHaveKeys(['chain', 'apy', 'protocol', 'bridge_fee', 'net_apy_estimate']);
    });
});
