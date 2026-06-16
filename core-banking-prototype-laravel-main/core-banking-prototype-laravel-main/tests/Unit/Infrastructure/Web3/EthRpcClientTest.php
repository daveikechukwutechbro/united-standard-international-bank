<?php

declare(strict_types=1);

use App\Infrastructure\Web3\EthRpcClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
    $this->client = new EthRpcClient();
});

describe('EthRpcClient', function () {
    describe('ethCall', function () {
        it('throws when no RPC URL is configured', function () {
            config(['web3.rpc_urls.ethereum' => null]);
            config(['crosschain.rpc_urls.ethereum' => null]);
            config(['defi.rpc_urls.ethereum' => null]);

            expect(fn () => $this->client->ethCall('0xContract', '0xData', 'ethereum'))
                ->toThrow(RuntimeException::class, 'No RPC URL configured');
        });

        it('makes successful eth_call and returns result', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            Http::fake([
                'rpc.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0x000000000000000000000000000000000000000000000000000000000000007b',
                ]),
            ]);

            $result = $this->client->ethCall('0xContract', '0xData', 'ethereum');

            expect($result)->toBe('0x000000000000000000000000000000000000000000000000000000000000007b');
        });

        it('throws on RPC error response', function () {
            config(['web3.rpc_urls.polygon' => 'https://rpc.polygon.example.com']);

            Http::fake([
                'rpc.polygon.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'error'   => ['code' => -32000, 'message' => 'execution reverted'],
                ]),
            ]);

            expect(fn () => $this->client->ethCall('0xContract', '0xData', 'polygon'))
                ->toThrow(RuntimeException::class, 'execution reverted');
        });

        it('resolves RPC URL from crosschain config', function () {
            config(['web3.rpc_urls.arbitrum' => null]);
            config(['crosschain.rpc_urls.arbitrum' => 'https://rpc.arbitrum.example.com']);

            Http::fake([
                'rpc.arbitrum.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0x01',
                ]),
            ]);

            $result = $this->client->ethCall('0xContract', '0xData', 'arbitrum');

            expect($result)->toBe('0x01');
        });

        it('resolves RPC URL from defi config', function () {
            config(['web3.rpc_urls.base' => null]);
            config(['crosschain.rpc_urls.base' => null]);
            config(['defi.rpc_urls.base' => 'https://rpc.base.example.com']);

            Http::fake([
                'rpc.base.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0x02',
                ]),
            ]);

            $result = $this->client->ethCall('0xContract', '0xData', 'base');

            expect($result)->toBe('0x02');
        });

        it('resets circuit breaker on success', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            // Simulate prior failures
            Cache::put('eth_rpc_failures:ethereum', 2, 300);

            Http::fake([
                'rpc.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0x00',
                ]),
            ]);

            $this->client->ethCall('0xContract', '0xData', 'ethereum');

            expect(Cache::get('eth_rpc_failures:ethereum'))->toBeNull();
        });
    });

    describe('circuit breaker', function () {
        it('opens after max failures', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            // Simulate max failures
            Cache::put('eth_rpc_failures:ethereum', 3, 300);

            expect(fn () => $this->client->ethCall('0xContract', '0xData', 'ethereum'))
                ->toThrow(RuntimeException::class, 'Circuit breaker open');
        });

        it('allows calls when below threshold', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            Cache::put('eth_rpc_failures:ethereum', 2, 300);

            Http::fake([
                'rpc.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0x00',
                ]),
            ]);

            $result = $this->client->ethCall('0xContract', '0xData', 'ethereum');

            expect($result)->toBe('0x00');
        });
    });

    describe('getTransactionReceipt', function () {
        it('returns null when no RPC URL configured', function () {
            config(['web3.rpc_urls.ethereum' => null]);
            config(['crosschain.rpc_urls.ethereum' => null]);
            config(['defi.rpc_urls.ethereum' => null]);

            $result = $this->client->getTransactionReceipt('0xTxHash', 'ethereum');

            expect($result)->toBeNull();
        });

        it('returns receipt data on success', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            Http::fake([
                'rpc.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => [
                        'status'      => '0x1',
                        'blockNumber' => '0x10',
                    ],
                ]),
            ]);

            $result = $this->client->getTransactionReceipt('0xTxHash', 'ethereum');

            expect($result)->not->toBeNull();
            expect($result['status'])->toBe('0x1');
        });

        it('returns null on failed request', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            Http::fake([
                'rpc.example.com' => Http::response('Server Error', 500),
            ]);

            $result = $this->client->getTransactionReceipt('0xTxHash', 'ethereum');

            expect($result)->toBeNull();
        });
    });

    describe('sendTransaction', function () {
        it('throws when no RPC URL is configured', function () {
            config(['web3.rpc_urls.ethereum' => null]);
            config(['crosschain.rpc_urls.ethereum' => null]);
            config(['defi.rpc_urls.ethereum' => null]);

            expect(fn () => $this->client->sendTransaction('0xFrom', '0xTo', '0xData', 'ethereum'))
                ->toThrow(RuntimeException::class, 'No RPC URL configured');
        });

        it('returns tx hash on success', function () {
            config(['web3.rpc_urls.ethereum' => 'https://rpc.example.com']);

            Http::fake([
                'rpc.example.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'result'  => '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
                ]),
            ]);

            $result = $this->client->sendTransaction('0xFrom', '0xTo', '0xData', 'ethereum');

            expect($result)->toBe('0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890');
        });
    });
});
