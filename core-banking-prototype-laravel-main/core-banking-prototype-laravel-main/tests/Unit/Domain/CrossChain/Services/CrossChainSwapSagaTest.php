<?php

declare(strict_types=1);

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\DemoBridgeAdapter;
use App\Domain\CrossChain\Services\BridgeOrchestratorService;
use App\Domain\CrossChain\Services\BridgeTransactionTracker;
use App\Domain\CrossChain\Services\CrossChainSwapSaga;
use App\Domain\CrossChain\Services\CrossChainSwapService;
use App\Domain\DeFi\Services\Connectors\DemoSwapConnector;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Domain\DeFi\Services\SwapAggregatorService;
use App\Domain\DeFi\Services\SwapRouterService;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->userUuid = 'test-saga-user-uuid';

    $this->bridgeOrchestrator = new BridgeOrchestratorService();
    $this->bridgeOrchestrator->registerAdapter(new DemoBridgeAdapter());

    $aggregator = new SwapAggregatorService();
    $aggregator->registerConnector(new DemoSwapConnector());
    $aggregator->registerConnector(new UniswapV3Connector(new AbiEncoder(), new EthRpcClient()));
    $this->swapRouter = new SwapRouterService($aggregator);

    $this->tracker = new BridgeTransactionTracker();
    $this->saga = new CrossChainSwapSaga($this->bridgeOrchestrator, $this->swapRouter, $this->tracker);

    $swapService = new CrossChainSwapService($this->bridgeOrchestrator, $this->swapRouter, $this->saga);
    $this->swapService = $swapService;

    // Register wallet addresses used in tests for the user (Finding #10)
    foreach (['0xSagaWallet', '0xStatusWallet', '0xE2EWallet'] as $addr) {
        BlockchainAddress::create([
            'user_uuid'  => $this->userUuid,
            'chain'      => 'ethereum',
            'address'    => $addr,
            'public_key' => '0xpubkey',
            'is_active'  => true,
        ]);
    }
});

describe('CrossChainSwapSaga', function () {
    it('executes bridge step and tracks transaction', function () {
        $quote = $this->swapService->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        $result = $this->saga->executeBridge($quote, '0xSagaWallet', $this->userUuid);

        expect($result)->toHaveKeys(['transaction_id', 'status']);
        expect($result['transaction_id'])->not->toBeEmpty();
    });

    it('executes swap after bridge successfully', function () {
        $quote = $this->swapService->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        $bridgeResult = $this->saga->executeBridge($quote, '0xSagaWallet', $this->userUuid);
        $swapResult = $this->saga->executeSwapAfterBridge($quote, '0xSagaWallet', $bridgeResult);

        expect($swapResult)->toHaveKeys(['tx_hash', 'output_amount']);
        expect($swapResult['tx_hash'])->not->toStartWith('failed_swap_');
    });

    it('returns compensation on swap failure with graceful degradation', function () {
        $quote = $this->swapService->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        $bridgeResult = [
            'transaction_id' => 'test_bridge_tx_123',
            'status'         => BridgeStatus::COMPLETED,
        ];

        // Create a saga with a swap router that will fail
        $failingAggregator = new SwapAggregatorService();
        // No connectors registered, so swap will fail
        $failingRouter = new SwapRouterService($failingAggregator);
        $failingSaga = new CrossChainSwapSaga($this->bridgeOrchestrator, $failingRouter, $this->tracker);

        $result = $failingSaga->executeSwapAfterBridge($quote, '0xSagaWallet', $bridgeResult);

        // Should return bridged amount as output (compensation)
        expect($result['tx_hash'])->toStartWith('failed_swap_');
        expect($result['output_amount'])->toBe($quote->bridgeQuote->outputAmount);
        expect($result['swap_failed'])->toBeTrue();
    });

    it('checks swap status for tracked transactions', function () {
        $quote = $this->swapService->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'USDC',
            '500.00',
        );

        $bridgeResult = $this->saga->executeBridge($quote, '0xStatusWallet', $this->userUuid);
        $status = $this->saga->checkSwapStatus($bridgeResult['transaction_id']);

        expect($status)->toHaveKeys(['status', 'bridge_status']);
    });

    it('returns unknown status for untracked transactions', function () {
        $status = $this->saga->checkSwapStatus('nonexistent_tx_id');

        expect($status['status'])->toBe('unknown');
        expect($status['bridge_status'])->toBe(BridgeStatus::FAILED);
    });

    it('full cross-chain swap completes successfully end-to-end', function () {
        $quote = $this->swapService->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            'WETH',
            '2000.00',
        );

        $result = $this->swapService->executeSwap($quote, '0xE2EWallet', $this->userUuid);

        expect($result['status'])->toBe('completed');
        expect($result['bridge_tx'])->not->toBeEmpty();
        expect($result['swap_tx'])->not->toBeNull();
        expect((float) $result['output_amount'])->toBeGreaterThan(0);
    });
});
