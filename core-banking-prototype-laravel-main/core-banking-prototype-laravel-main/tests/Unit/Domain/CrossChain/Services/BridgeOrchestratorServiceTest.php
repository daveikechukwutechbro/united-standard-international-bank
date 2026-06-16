<?php

declare(strict_types=1);

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Exceptions\BridgeTransactionFailedException;
use App\Domain\CrossChain\Exceptions\UnsupportedBridgeRouteException;
use App\Domain\CrossChain\Services\BridgeOrchestratorService;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new BridgeOrchestratorService();
});

function createMockAdapter(
    BridgeProvider $provider,
    array $supportedRoutes = [],
    bool $supportsRoute = true,
): BridgeAdapterInterface&Mockery\MockInterface {
    $mock = Mockery::mock(BridgeAdapterInterface::class);
    $mock->shouldReceive('getProvider')->andReturn($provider);
    $mock->shouldReceive('getSupportedRoutes')->andReturn($supportedRoutes);
    $mock->shouldReceive('supportsRoute')->andReturn($supportsRoute);

    return $mock;
}

function createBridgeQuote(
    BridgeProvider $provider = BridgeProvider::DEMO,
    string $fee = '1.00',
    int $estimatedTime = 120,
    ?CarbonImmutable $expiresAt = null,
): BridgeQuote {
    $route = new BridgeRoute(
        CrossChainNetwork::ETHEREUM,
        CrossChainNetwork::POLYGON,
        'USDC',
        $provider,
        $estimatedTime,
        $fee,
    );

    return new BridgeQuote(
        quoteId: 'quote-' . uniqid(),
        route: $route,
        inputAmount: '1000.00',
        outputAmount: '999.00',
        fee: $fee,
        feeCurrency: 'USDC',
        estimatedTimeSeconds: $estimatedTime,
        expiresAt: $expiresAt ?? CarbonImmutable::now()->addMinutes(5),
    );
}

describe('BridgeOrchestratorService', function () {
    it('registers and retrieves adapters', function () {
        $adapter = createMockAdapter(BridgeProvider::DEMO);
        $this->service->registerAdapter($adapter);

        expect($this->service->getAdapters())->toHaveKey('demo');
    });

    it('gets quotes from all supporting adapters', function () {
        $quote1 = createBridgeQuote(BridgeProvider::DEMO, '1.50');
        $adapter1 = createMockAdapter(BridgeProvider::DEMO);
        $adapter1->shouldReceive('getQuote')->andReturn($quote1);

        $quote2 = createBridgeQuote(BridgeProvider::WORMHOLE, '2.00');
        $adapter2 = createMockAdapter(BridgeProvider::WORMHOLE);
        $adapter2->shouldReceive('getQuote')->andReturn($quote2);

        $this->service->registerAdapter($adapter1);
        $this->service->registerAdapter($adapter2);

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($quotes)->toHaveCount(2);
    });

    it('throws when no adapters support the route', function () {
        $adapter = createMockAdapter(BridgeProvider::DEMO, [], false);
        $this->service->registerAdapter($adapter);

        $this->service->getQuotes(
            CrossChainNetwork::BITCOIN,
            CrossChainNetwork::TRON,
            'BTC',
            '1.0',
        );
    })->throws(UnsupportedBridgeRouteException::class);

    it('gets best quote by lowest fee', function () {
        $cheapQuote = createBridgeQuote(BridgeProvider::DEMO, '0.50', 300);
        $expensiveQuote = createBridgeQuote(BridgeProvider::WORMHOLE, '2.00', 120);

        $adapter1 = createMockAdapter(BridgeProvider::DEMO);
        $adapter1->shouldReceive('getQuote')->andReturn($cheapQuote);

        $adapter2 = createMockAdapter(BridgeProvider::WORMHOLE);
        $adapter2->shouldReceive('getQuote')->andReturn($expensiveQuote);

        $this->service->registerAdapter($adapter1);
        $this->service->registerAdapter($adapter2);

        $best = $this->service->getBestQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($best->fee)->toBe('0.50');
    });

    it('gets fastest quote by estimated time', function () {
        $slowQuote = createBridgeQuote(BridgeProvider::DEMO, '0.50', 600);
        $fastQuote = createBridgeQuote(BridgeProvider::WORMHOLE, '2.00', 60);

        $adapter1 = createMockAdapter(BridgeProvider::DEMO);
        $adapter1->shouldReceive('getQuote')->andReturn($slowQuote);

        $adapter2 = createMockAdapter(BridgeProvider::WORMHOLE);
        $adapter2->shouldReceive('getQuote')->andReturn($fastQuote);

        $this->service->registerAdapter($adapter1);
        $this->service->registerAdapter($adapter2);

        $fastest = $this->service->getFastestQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($fastest->estimatedTimeSeconds)->toBe(60);
    });

    it('initiates bridge transfer successfully', function () {
        $quote = createBridgeQuote(BridgeProvider::DEMO);
        $userUuid = 'test-user-uuid-001';

        // Seed server-side quote cache (Finding #3 requirement)
        config(['cache.default' => 'array']);
        Cache::put("bridge_quote:{$quote->quoteId}", $quote->toArray(), 60);

        // Register a verified sender address for this user (Finding #10 requirement)
        BlockchainAddress::create([
            'user_uuid'  => $userUuid,
            'chain'      => 'ethereum',
            'address'    => '0xSender',
            'public_key' => '0xpubkey',
            'is_active'  => true,
        ]);

        $adapter = createMockAdapter(BridgeProvider::DEMO);
        $adapter->shouldReceive('initiateBridge')->andReturn([
            'transaction_id' => 'tx-123',
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => '0xabc',
        ]);

        $this->service->registerAdapter($adapter);

        $result = $this->service->initiateBridge($quote->quoteId, '0xSender', '0xRecipient', $userUuid);

        expect($result['transaction_id'])->toBe('tx-123');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
    });

    it('rejects expired quotes', function () {
        $expiredQuote = createBridgeQuote(
            expiresAt: CarbonImmutable::now()->subMinute(),
        );

        // Seed server-side quote cache (Finding #3 requirement)
        config(['cache.default' => 'array']);
        Cache::put("bridge_quote:{$expiredQuote->quoteId}", $expiredQuote->toArray(), 60);

        $adapter = createMockAdapter(BridgeProvider::DEMO);
        $this->service->registerAdapter($adapter);

        $this->service->initiateBridge($expiredQuote->quoteId, '0xSender', '0xRecipient', 'any-user-uuid');
    })->throws(BridgeTransactionFailedException::class, 'expired');

    it('checks bridge transaction status', function () {
        $adapter = createMockAdapter(BridgeProvider::DEMO);
        $adapter->shouldReceive('getBridgeStatus')->with('tx-123')->andReturn([
            'status'         => BridgeStatus::BRIDGING,
            'source_tx_hash' => '0xabc',
            'dest_tx_hash'   => null,
            'confirmations'  => 5,
        ]);

        $this->service->registerAdapter($adapter);

        $result = $this->service->checkStatus(
            'tx-123',
            BridgeProvider::DEMO,
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($result['status'])->toBe(BridgeStatus::BRIDGING);
        expect($result['confirmations'])->toBe(5);
    });

    it('gets all supported routes from all adapters', function () {
        $route = new BridgeRoute(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            BridgeProvider::DEMO,
            120,
            '1.00',
        );

        $adapter = createMockAdapter(BridgeProvider::DEMO, [$route]);
        $this->service->registerAdapter($adapter);

        $routes = $this->service->getAllSupportedRoutes();

        expect($routes)->toHaveCount(1);
        expect($routes[0]->sourceChain)->toBe(CrossChainNetwork::ETHEREUM);
    });

    it('gets supported chains from routes', function () {
        $route = new BridgeRoute(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            BridgeProvider::DEMO,
            120,
            '1.00',
        );

        $adapter = createMockAdapter(BridgeProvider::DEMO, [$route]);
        $this->service->registerAdapter($adapter);

        $chains = $this->service->getSupportedChains();

        expect($chains)->toContain(CrossChainNetwork::ETHEREUM);
        expect($chains)->toContain(CrossChainNetwork::POLYGON);
    });

    it('gracefully handles adapter quote failures', function () {
        $failingAdapter = createMockAdapter(BridgeProvider::WORMHOLE);
        $failingAdapter->shouldReceive('getQuote')->andThrow(new RuntimeException('API error'));

        $workingQuote = createBridgeQuote(BridgeProvider::DEMO);
        $workingAdapter = createMockAdapter(BridgeProvider::DEMO);
        $workingAdapter->shouldReceive('getQuote')->andReturn($workingQuote);

        $this->service->registerAdapter($failingAdapter);
        $this->service->registerAdapter($workingAdapter);

        $quotes = $this->service->getQuotes(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($quotes)->toHaveCount(1);
    });
});
