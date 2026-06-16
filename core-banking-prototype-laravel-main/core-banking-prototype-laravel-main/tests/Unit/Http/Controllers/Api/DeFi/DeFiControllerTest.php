<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\AaveV3Connector;
use App\Domain\DeFi\Services\Connectors\DemoSwapConnector;
use App\Domain\DeFi\Services\Connectors\LidoConnector;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Domain\DeFi\Services\DeFiPortfolioService;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;
use App\Domain\DeFi\Services\SwapAggregatorService;
use App\Domain\DeFi\Services\SwapRouterService;
use App\Http\Controllers\Api\DeFi\DeFiController;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;
use Illuminate\Http\Request;

uses(Tests\TestCase::class);

function makeDefiPostRequest(string $uri, array $data): Request
{
    $json = json_encode($data);

    return Request::create($uri, 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT'  => 'application/json',
    ], $json);
}

beforeEach(function () {
    $encoder = new AbiEncoder();
    $rpcClient = new EthRpcClient();

    $aggregator = new SwapAggregatorService();
    $aggregator->registerConnector(new DemoSwapConnector());
    $aggregator->registerConnector(new UniswapV3Connector($encoder, $rpcClient));
    $this->swapRouter = new SwapRouterService($aggregator);

    $this->positionTracker = new DeFiPositionTrackerService();
    $this->portfolioService = new DeFiPortfolioService($this->positionTracker);

    $this->controller = new DeFiController(
        $this->swapRouter,
        $this->portfolioService,
        $this->positionTracker,
        new AaveV3Connector($encoder, $rpcClient),
        new LidoConnector(),
    );
});

describe('DeFiController', function () {
    it('returns list of supported protocols', function () {
        $response = $this->controller->protocols();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeArray();
        expect(count($data['data']))->toBeGreaterThanOrEqual(4);

        $names = array_column($data['data'], 'name');
        expect($names)->toContain('uniswap_v3');
        expect($names)->toContain('aave_v3');
    });

    it('returns swap quote', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/quote', [
            'chain'      => 'ethereum',
            'from_token' => 'USDC',
            'to_token'   => 'WETH',
            'amount'     => '1000.00',
        ]);

        $response = $this->controller->swapQuote($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKeys([
            'quote_id', 'chain', 'input_token', 'output_token',
            'input_amount', 'output_amount', 'price_impact', 'protocol',
        ]);
    });

    it('executes swap', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/execute', [
            'chain'          => 'ethereum',
            'from_token'     => 'USDC',
            'to_token'       => 'WETH',
            'amount'         => '500.00',
            'wallet_address' => '0xTestWallet',
        ]);

        $response = $this->controller->swapExecute($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKeys(['tx_hash', 'output_amount', 'protocol']);
    });

    it('returns lending markets', function () {
        $request = Request::create('/api/v1/defi/lending/markets?chain=ethereum');

        $response = $this->controller->lendingMarkets($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeArray();
        expect(count($data['data']))->toBeGreaterThan(0);
        expect($data['data'][0])->toHaveKeys(['token', 'supply_apy', 'borrow_apy']);
    });

    it('returns empty markets for unsupported chain', function () {
        $request = Request::create('/api/v1/defi/lending/markets?chain=bitcoin');

        $response = $this->controller->lendingMarkets($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeEmpty();
    });

    it('returns portfolio summary', function () {
        $request = Request::create('/api/v1/defi/portfolio?wallet_address=0xPortfolioUser');

        $response = $this->controller->portfolio($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKeys(['total_value_usd', 'positions_count']);
    });

    it('returns active positions', function () {
        $this->positionTracker->openPosition(
            DeFiProtocol::AAVE_V3,
            DeFiPositionType::SUPPLY,
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '5000.00',
            '5000.00',
            '3.50',
            '0xPositionUser',
        );

        $request = Request::create('/api/v1/defi/positions?wallet_address=0xPositionUser');

        $response = $this->controller->positions($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeArray();
        expect(count($data['data']))->toBe(1);
    });

    it('filters positions by chain', function () {
        $this->positionTracker->openPosition(
            DeFiProtocol::AAVE_V3,
            DeFiPositionType::SUPPLY,
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '1000.00',
            '1000.00',
            '3.50',
            '0xFilterUser',
        );
        $this->positionTracker->openPosition(
            DeFiProtocol::AAVE_V3,
            DeFiPositionType::SUPPLY,
            CrossChainNetwork::POLYGON,
            'USDC',
            '2000.00',
            '2000.00',
            '3.50',
            '0xFilterUser',
        );

        $request = Request::create('/api/v1/defi/positions?wallet_address=0xFilterUser&chain=ethereum');

        $response = $this->controller->positions($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect(count($data['data']))->toBe(1);
    });

    it('returns staking info', function () {
        $request = Request::create('/api/v1/defi/staking?chain=ethereum&wallet_address=0xStaker');

        $response = $this->controller->staking($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKeys(['protocol', 'staking_apy', 'staked_balance']);
        expect($data['data']['protocol'])->toBe('lido');
    });

    it('returns yield opportunities', function () {
        $request = Request::create('/api/v1/defi/yield?wallet_address=0xYieldUser');

        $response = $this->controller->yield($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeArray();
    });

    it('rejects portfolio request without wallet_address', function () {
        $request = Request::create('/api/v1/defi/portfolio');

        $this->controller->portfolio($request);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('rejects yield request without wallet_address', function () {
        $request = Request::create('/api/v1/defi/yield');

        $this->controller->yield($request);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('rejects slippage above 5 percent', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/quote', [
            'chain'      => 'ethereum',
            'from_token' => 'USDC',
            'to_token'   => 'WETH',
            'amount'     => '1000.00',
            'slippage'   => 10.0,
        ]);

        $this->controller->swapQuote($request);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('rejects invalid chain enum in swap quote', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/quote', [
            'chain'      => 'invalid_chain',
            'from_token' => 'USDC',
            'to_token'   => 'WETH',
            'amount'     => '1000.00',
        ]);

        $this->controller->swapQuote($request);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('handles swap with custom slippage', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/quote', [
            'chain'      => 'polygon',
            'from_token' => 'WETH',
            'to_token'   => 'USDC',
            'amount'     => '1.00',
            'slippage'   => 1.0,
        ]);

        $response = $this->controller->swapQuote($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
    });

    it('validates chain enum for invalid chain in swap quote', function () {
        $request = makeDefiPostRequest('/api/v1/defi/swap/quote', [
            'chain'      => 'invalid_chain',
            'from_token' => 'USDC',
            'to_token'   => 'WETH',
            'amount'     => '1000.00',
        ]);

        $this->controller->swapQuote($request);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('returns lending markets for polygon', function () {
        $request = Request::create('/api/v1/defi/lending/markets?chain=polygon');

        $response = $this->controller->lendingMarkets($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue();
        expect($data['data'])->toBeArray();
    });
});
