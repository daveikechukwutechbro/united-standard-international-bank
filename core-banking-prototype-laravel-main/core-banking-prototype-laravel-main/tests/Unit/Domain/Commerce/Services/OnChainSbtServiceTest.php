<?php

declare(strict_types=1);

use App\Domain\Commerce\Services\OnChainSbtService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

describe('OnChainSbtService', function (): void {
    describe('isAvailable', function (): void {
        it('returns false when signer address is empty', function (): void {
            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '',
                signerPrivateKey: 'key',
            );

            expect($service->isAvailable())->toBeFalse();
        });

        it('returns false when signer private key is empty', function (): void {
            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '',
            );

            expect($service->isAvailable())->toBeFalse();
        });

        it('returns true when properly configured and RPC responds', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response(['jsonrpc' => '2.0', 'result' => '0x89', 'id' => 1]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            expect($service->isAvailable())->toBeTrue();
        });

        it('returns false when RPC fails', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response(null, 500),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            expect($service->isAvailable())->toBeFalse();
        });
    });

    describe('deployContract', function (): void {
        it('throws when service is not available', function (): void {
            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '',
                signerPrivateKey: '',
            );

            $service->deployContract('FinAegis SBT', 'FASBT', 'https://finaegis.com/sbt/');
        })->throws(RuntimeException::class, 'On-chain SBT service is not available');

        it('deploys a contract and returns result', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::sequence()
                    ->push(['jsonrpc' => '2.0', 'result' => '0xdeploymenthash123456', 'id' => 1]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            $result = $service->deployContract('FinAegis SBT', 'FASBT', 'https://finaegis.com/sbt/');

            expect($result)->toHaveKeys(['contract_address', 'tx_hash', 'network']);
            expect($result['network'])->toBe('polygon');
            expect($result['tx_hash'])->toBeString();
            expect($result['contract_address'])->toStartWith('0x');
        });
    });

    describe('mintToken', function (): void {
        it('throws when service is not available', function (): void {
            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '',
                signerPrivateKey: '',
            );

            $service->mintToken('0xcontract', '0xrecipient', 'https://finaegis.com/sbt/1');
        })->throws(RuntimeException::class);

        it('mints a token and returns result', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::sequence()
                    ->push(['jsonrpc' => '2.0', 'result' => '0xminthash123456', 'id' => 1])
                    ->push(['jsonrpc' => '2.0', 'result' => ['logs' => [['topics' => ['0xddf252', '0x0', '0xrecipient', '0x000000000000000000000000000000000000000000000000000000000000002a']]]], 'id' => 1]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            $result = $service->mintToken('0xcontract', '0xrecipient', 'https://finaegis.com/sbt/1');

            expect($result)->toHaveKeys(['token_id', 'tx_hash', 'contract_address', 'network']);
            expect($result['token_id'])->toBeInt();
            expect($result['network'])->toBe('polygon');
        });
    });

    describe('revokeToken', function (): void {
        it('revokes a token and returns result', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response(['jsonrpc' => '2.0', 'result' => '0xrevokehash123', 'id' => 1]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            $result = $service->revokeToken('0xcontract', 1);

            expect($result)->toHaveKeys(['tx_hash', 'contract_address', 'network']);
            expect($result['network'])->toBe('polygon');
        });
    });

    describe('isTokenValid', function (): void {
        it('returns true when ownerOf succeeds', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'result'  => '0x000000000000000000000000abcdef1234567890abcdef1234567890abcdef12',
                    'id'      => 1,
                ]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            expect($service->isTokenValid('0xcontract', 1))->toBeTrue();
        });

        it('returns false when ownerOf returns error', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'error'   => ['message' => 'execution reverted'],
                    'id'      => 1,
                ]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            expect($service->isTokenValid('0xcontract', 999))->toBeFalse();
        });
    });

    describe('getTokenUri', function (): void {
        it('returns decoded string from eth_call result', function (): void {
            $uri = 'https://finaegis.com/sbt/1';
            $hexUri = bin2hex($uri);
            $len = dechex(strlen($uri));
            $encodedResult = '0x'
                . str_pad(dechex(32), 64, '0', STR_PAD_LEFT)
                . str_pad($len, 64, '0', STR_PAD_LEFT)
                . str_pad($hexUri, 64, '0', STR_PAD_RIGHT);

            Http::fake([
                'polygon-rpc.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'result'  => $encodedResult,
                    'id'      => 1,
                ]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            $result = $service->getTokenUri('0xcontract', 1);
            expect($result)->toBe($uri);
        });
    });

    describe('sendTransaction error handling', function (): void {
        it('throws on RPC error response', function (): void {
            Http::fake([
                'polygon-rpc.com' => Http::response([
                    'jsonrpc' => '2.0',
                    'error'   => ['message' => 'insufficient funds'],
                    'id'      => 1,
                ]),
            ]);

            $service = new OnChainSbtService(
                rpcUrl: 'https://polygon-rpc.com',
                network: 'polygon',
                signerAddress: '0x1234567890abcdef1234567890abcdef12345678',
                signerPrivateKey: '0xprivatekey',
            );

            $service->deployContract('SBT', 'S', 'uri');
        })->throws(RuntimeException::class, 'Transaction failed: insufficient funds');
    });
});
