<?php

declare(strict_types=1);

use App\Domain\Account\Models\BlockchainAddress;
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
    $this->userUuid = 'test-swap-service-user-uuid';

    $bridgeOrchestrator = new BridgeOrchestratorService();
    $bridgeOrchestrator->registerAdapter(new DemoBridgeAdapter());

    $aggregator = new SwapAggregatorService();
    $aggregator->registerConnector(new DemoSwapConnector());
    $aggregator->registerConnector(new UniswapV3Connector(new AbiEncoder(), new EthRpcClient()));
    $swapRouter = new SwapRouterService($aggregator);

    $tracker = new BridgeTransactionTracker();
    $saga = new CrossChainSwapSaga($bridgeOrchestrator, $swapRouter, $tracker);

    $this->service = new CrossChainSwapService($bridgeOrchestrator, $swapRouter, $saga);

    // Register wallet addresses used in tests for the user (Finding #10)
    foreach (['0xWallet123', '0xWallet456'] as $addr) {
        BlockchainAddress::create([
            'user_uuid'  => $this->userUuid,
            'chain'      => 'ethereum',
            'address'    => $addr,
            'public_key' => '0xpubkey',
            'is_active'  => true,
        ]);
    }
});

describe('CrossChainSwapService', function () {
    it('gets quote for cross-chain swap with different tokens', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quote->quoteId)->toStartWith('xswap_');
        expect($quote->sourceChain)->toBe(CrossChainNetwork::ETHEREUM);
        expect($quote->destChain)->toBe(CrossChainNetwork::POLYGON);
        expect($quote->inputToken)->toBe('USDC');
        expect($quote->outputToken)->toBe('WETH');
        expect($quote->requiresSwap())->toBeTrue();
        expect($quote->swapQuote)->not->toBeNull();
    });

    it('gets quote for bridge-only when tokens match', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            'USDC',
            '5000.00',
        );

        expect($quote->requiresSwap())->toBeFalse();
        expect($quote->swapQuote)->toBeNull();
        expect($quote->inputToken)->toBe('USDC');
        expect($quote->outputToken)->toBe('USDC');
    });

    it('calculates total fee including bridge and swap', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect((float) $quote->totalFee)->toBeGreaterThan(0);
        expect($quote->feeCurrency)->not->toBeEmpty();
    });

    it('estimates total time including bridge and swap', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        // Bridge time + 30s for swap
        expect($quote->estimatedTimeSeconds)->toBeGreaterThan(30);
    });

    it('executes a cross-chain swap with bridge and swap steps', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'WETH',
            '1000.00',
        );

        $result = $this->service->executeSwap($quote, '0xWallet123', $this->userUuid);

        expect($result['bridge_tx'])->not->toBeEmpty();
        expect($result['swap_tx'])->not->toBeNull();
        expect($result['output_amount'])->not->toBe('0');
        expect($result['status'])->toBe('completed');
    });

    it('executes bridge-only swap when tokens match', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            'USDC',
            '2000.00',
        );

        $result = $this->service->executeSwap($quote, '0xWallet456', $this->userUuid);

        expect($result['bridge_tx'])->not->toBeEmpty();
        expect($result['swap_tx'])->toBeNull();
        expect($result['status'])->toBe('completed');
    });

    it('returns serializable quote via toArray', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            'WETH',
            '500.00',
        );

        $array = $quote->toArray();

        expect($array)->toHaveKeys([
            'quote_id', 'source_chain', 'dest_chain', 'input_token',
            'output_token', 'input_amount', 'estimated_output_amount',
            'bridge_quote', 'total_fee', 'estimated_time_seconds',
        ]);
    });

    it('works across different chain pairs', function () {
        $quote = $this->service->getQuote(
            CrossChainNetwork::ARBITRUM,
            CrossChainNetwork::OPTIMISM,
            'WETH',
            'USDC',
            '1.00',
        );

        expect($quote->sourceChain)->toBe(CrossChainNetwork::ARBITRUM);
        expect($quote->destChain)->toBe(CrossChainNetwork::OPTIMISM);
    });
});
