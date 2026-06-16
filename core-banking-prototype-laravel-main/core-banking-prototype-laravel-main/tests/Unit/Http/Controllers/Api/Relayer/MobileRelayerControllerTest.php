<?php

declare(strict_types=1);

use App\Domain\Relayer\Contracts\BundlerInterface;
use App\Domain\Relayer\Enums\SupportedNetwork;
use App\Domain\Relayer\Services\GasStationService;
use App\Domain\Relayer\Services\SmartAccountService;
use App\Http\Controllers\Api\Relayer\MobileRelayerController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

uses(UnitTestCase::class);

beforeEach(function (): void {
    $this->gasStation = Mockery::mock(GasStationService::class);
    $this->smartAccountService = Mockery::mock(SmartAccountService::class);
    $this->bundler = Mockery::mock(BundlerInterface::class);
});

function makeRelayerController($test): MobileRelayerController
{
    return new MobileRelayerController(
        $test->gasStation,
        $test->smartAccountService,
        $test->bundler,
    );
}

function relayerUserRequest(string $uri, string $method = 'GET', array $data = []): Request
{
    if ($method === 'POST') {
        $request = Request::create($uri, $method, $data, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    } else {
        $request = Request::create($uri, $method, $data);
    }
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 1;
    $request->setUserResolver(fn () => $user);

    return $request;
}

describe('MobileRelayerController status', function (): void {
    it('returns relayer health and gas prices', function (): void {
        $controller = makeRelayerController($this);

        $response = $controller->status();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['healthy'])->toBeTrue()
            ->and($data['data']['networks'])->toBeArray()
            ->and(count($data['data']['networks']))->toBe(5);

        $polygon = collect($data['data']['networks'])->firstWhere('network', 'polygon');
        expect($polygon)->toHaveKeys(['network', 'chain_id', 'gas_price_gwei', 'congestion']);
    });
});

describe('MobileRelayerController estimateGas', function (): void {
    it('estimates gas for a valid network', function (): void {
        $this->gasStation->shouldReceive('estimateFee')
            ->with('0x', SupportedNetwork::POLYGON)
            ->andReturn(['estimated_gas' => 200000, 'fee_usdc' => '0.020000', 'fee_usdt' => '0.020000', 'network' => 'polygon']);

        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/estimate-gas', 'POST', [
            'network' => 'polygon',
            'to'      => '0xrecipient',
        ]);

        $response = $controller->estimateGas($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['network'])->toBe('polygon')
            ->and($data['data']['sponsored'])->toBeTrue();
    });

    it('returns 422 for unsupported network', function (): void {
        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/estimate-gas', 'POST', [
            'network' => 'invalid_network',
            'to'      => '0xrecipient',
        ]);

        $response = $controller->estimateGas($request);

        expect($response->getStatusCode())->toBe(422);
    });
});

describe('MobileRelayerController buildUserOp', function (): void {
    it('builds a UserOperation', function (): void {
        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/build-userop', 'POST', [
            'network' => 'polygon',
            'to'      => '0xrecipient',
        ]);

        $response = $controller->buildUserOp($request);
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['user_op'])->toBeArray()
            ->and($data['data']['entry_point'])->not->toBeEmpty()
            ->and($data['data']['network'])->toBe('polygon');
    });
});

describe('MobileRelayerController submitUserOp', function (): void {
    it('submits a signed UserOperation via bundler', function (): void {
        $this->bundler->shouldReceive('submitUserOperation')
            ->once()
            ->andReturn('0xreal_bundler_hash_abc123');

        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/submit', 'POST', [
            'network' => 'polygon',
            'user_op' => [
                'sender'               => '0x123',
                'nonce'                => '0x0',
                'initCode'             => '0x',
                'callData'             => '0x',
                'callGasLimit'         => '0x30D40',
                'verificationGasLimit' => '0x186A0',
                'preVerificationGas'   => '0xC350',
                'maxFeePerGas'         => '0x6FC23AC00',
                'maxPriorityFeePerGas' => '0x59682F00',
                'paymasterAndData'     => '0x',
            ],
            'signature' => '0xsig',
        ]);

        $response = $controller->submitUserOp($request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(201)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['user_op_hash'])->toBe('0xreal_bundler_hash_abc123')
            ->and($data['data']['status'])->toBe('pending');
    });

    it('returns 502 when bundler fails', function (): void {
        $this->bundler->shouldReceive('submitUserOperation')
            ->once()
            ->andThrow(new RuntimeException('Bundler unavailable'));

        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/submit', 'POST', [
            'network'   => 'polygon',
            'user_op'   => ['sender' => '0x123'],
            'signature' => '0xsig',
        ]);

        $response = $controller->submitUserOp($request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(502)
            ->and($data['success'])->toBeFalse()
            ->and($data['error']['code'])->toBe('BUNDLER_ERROR');
    });

    it('returns 422 for unsupported network', function (): void {
        $controller = makeRelayerController($this);

        $request = relayerUserRequest('/api/v1/relayer/submit', 'POST', [
            'network'   => 'invalid_network',
            'user_op'   => ['sender' => '0x123'],
            'signature' => '0xsig',
        ]);

        $response = $controller->submitUserOp($request);

        expect($response->getStatusCode())->toBe(422);
    });
});

describe('MobileRelayerController getUserOp', function (): void {
    it('returns confirmed status from bundler', function (): void {
        $this->bundler->shouldReceive('getUserOperationStatus')
            ->with('0xhash123')
            ->once()
            ->andReturn([
                'status'  => 'success',
                'tx_hash' => '0xtx_hash_abc',
                'receipt' => ['blockNumber' => 12345678],
            ]);

        $controller = makeRelayerController($this);

        $response = $controller->getUserOp('0xhash123');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['user_op_hash'])->toBe('0xhash123')
            ->and($data['data']['status'])->toBe('confirmed')
            ->and($data['data']['tx_hash'])->toBe('0xtx_hash_abc')
            ->and($data['data']['block_number'])->toBe(12345678);
    });

    it('returns pending status when bundler status unknown', function (): void {
        $this->bundler->shouldReceive('getUserOperationStatus')
            ->with('0xhash_pending')
            ->once()
            ->andReturn([
                'status'  => 'not_found',
                'tx_hash' => null,
                'receipt' => null,
            ]);

        $controller = makeRelayerController($this);

        $response = $controller->getUserOp('0xhash_pending');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['status'])->toBe('pending')
            ->and($data['data']['tx_hash'])->toBeNull();
    });

    it('returns failed status when UserOp reverted', function (): void {
        $this->bundler->shouldReceive('getUserOperationStatus')
            ->with('0xhash_failed')
            ->once()
            ->andReturn([
                'status'  => 'reverted',
                'tx_hash' => '0xtx_reverted',
                'receipt' => ['blockNumber' => 99999999],
            ]);

        $controller = makeRelayerController($this);

        $response = $controller->getUserOp('0xhash_failed');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['status'])->toBe('failed')
            ->and($data['data']['tx_hash'])->toBe('0xtx_reverted');
    });

    it('gracefully falls back to pending on bundler error', function (): void {
        $this->bundler->shouldReceive('getUserOperationStatus')
            ->with('0xhash_error')
            ->once()
            ->andThrow(new RuntimeException('RPC timeout'));

        $controller = makeRelayerController($this);

        $response = $controller->getUserOp('0xhash_error');
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data']['status'])->toBe('pending')
            ->and($data['data']['tx_hash'])->toBeNull();
    });
});

describe('MobileRelayerController supportedTokens', function (): void {
    it('returns supported gas payment tokens', function (): void {
        $controller = makeRelayerController($this);

        $response = $controller->supportedTokens();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeArray()
            ->and(count($data['data']))->toBeGreaterThan(0);

        $symbols = array_column($data['data'], 'symbol');
        expect($symbols)->toContain('USDC');
    });
});

describe('MobileRelayerController paymasterData', function (): void {
    it('returns paymaster configuration', function (): void {
        $controller = makeRelayerController($this);

        $response = $controller->paymasterData();
        $data = $response->getData(true);

        expect($data['success'])->toBeTrue()
            ->and($data['data'])->toBeArray()
            ->and(count($data['data']))->toBe(5);

        $polygon = collect($data['data'])->firstWhere('network', 'polygon');
        expect($polygon)->toHaveKeys(['paymaster_address', 'entry_point', 'sponsored_tokens']);
    });
});

describe('Relayer routes', function (): void {
    it('has relayer status route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.relayer.status');
        expect($route)->not->toBeNull();
    });

    it('has relayer estimate-gas route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.relayer.estimate-gas');
        expect($route)->not->toBeNull();
    });

    it('has relayer supported-tokens route defined', function (): void {
        $route = app('router')->getRoutes()->getByName('mobile.relayer.supported-tokens');
        expect($route)->not->toBeNull();
    });
});
