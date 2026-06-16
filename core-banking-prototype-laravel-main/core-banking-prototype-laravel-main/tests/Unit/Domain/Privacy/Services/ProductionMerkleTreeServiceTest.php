<?php

declare(strict_types=1);

use App\Domain\Privacy\Services\ProductionMerkleTreeService;
use App\Domain\Privacy\ValueObjects\MerklePath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Cache::flush();
    config(['privacy.merkle.networks' => ['polygon', 'base', 'arbitrum']]);
    config(['privacy.merkle.max_tree_depth' => 32]);
    config(['privacy.merkle.hash_algorithm' => 'sha3-256']);
});

describe('ProductionMerkleTreeService', function (): void {
    describe('getProviderName', function (): void {
        it('returns production', function (): void {
            $service = new ProductionMerkleTreeService();
            expect($service->getProviderName())->toBe('production');
        });
    });

    describe('supportsNetwork', function (): void {
        it('supports configured networks', function (): void {
            $service = new ProductionMerkleTreeService();
            expect($service->supportsNetwork('polygon'))->toBeTrue();
            expect($service->supportsNetwork('base'))->toBeTrue();
            expect($service->supportsNetwork('arbitrum'))->toBeTrue();
        });

        it('rejects unsupported networks', function (): void {
            $service = new ProductionMerkleTreeService();
            expect($service->supportsNetwork('solana'))->toBeFalse();
        });
    });

    describe('getSupportedNetworks', function (): void {
        it('returns configured networks', function (): void {
            $service = new ProductionMerkleTreeService();
            expect($service->getSupportedNetworks())->toBe(['polygon', 'base', 'arbitrum']);
        });
    });

    describe('getTreeDepth', function (): void {
        it('returns configured tree depth', function (): void {
            $service = new ProductionMerkleTreeService();
            expect($service->getTreeDepth())->toBe(32);
        });
    });

    describe('getMerkleRoot', function (): void {
        it('throws for unsupported network', function (): void {
            $service = new ProductionMerkleTreeService();
            $service->getMerkleRoot('solana');
        })->throws(InvalidArgumentException::class);

        it('fetches root from chain via RPC', function (): void {
            config(['privacy.merkle.pool_addresses.polygon' => '0xpoolcontract']);

            Http::fake([
                'polygon-rpc.com' => Http::sequence()
                    ->push(['jsonrpc' => '2.0', 'result' => '0x' . str_repeat('ab', 32), 'id' => 1]) // merkleRoot
                    ->push(['jsonrpc' => '2.0', 'result' => '0x0a', 'id' => 1]) // leafCount
                    ->push(['jsonrpc' => '2.0', 'result' => '0x1000', 'id' => 1]), // blockNumber
            ]);

            $service = new ProductionMerkleTreeService();
            $root = $service->getMerkleRoot('polygon');

            expect($root->network)->toBe('polygon');
            expect($root->root)->toStartWith('0x');
            expect($root->treeDepth)->toBe(32);
        });

        it('throws when pool address not configured', function (): void {
            config(['privacy.merkle.pool_addresses.polygon' => null]);

            $service = new ProductionMerkleTreeService();
            $service->getMerkleRoot('polygon');
        })->throws(RuntimeException::class);
    });

    describe('verifyCommitment', function (): void {
        it('returns false for invalid commitment format', function (): void {
            $service = new ProductionMerkleTreeService();
            $path = new MerklePath(
                commitment: 'invalid',
                root: '0x' . str_repeat('00', 32),
                network: 'polygon',
                leafIndex: 0,
                siblings: [],
                pathIndices: [],
            );

            expect($service->verifyCommitment('invalid', $path))->toBeFalse();
        });

        it('returns false for invalid sibling format', function (): void {
            $service = new ProductionMerkleTreeService();
            $path = new MerklePath(
                commitment: '0x' . str_repeat('aa', 32),
                root: '0x' . str_repeat('00', 32),
                network: 'polygon',
                leafIndex: 0,
                siblings: ['invalid_sibling'],
                pathIndices: [0],
            );

            expect($service->verifyCommitment('0x' . str_repeat('aa', 32), $path))->toBeFalse();
        });

        it('returns false for invalid path indices', function (): void {
            $service = new ProductionMerkleTreeService();
            $path = new MerklePath(
                commitment: '0x' . str_repeat('aa', 32),
                root: '0x' . str_repeat('00', 32),
                network: 'polygon',
                leafIndex: 0,
                siblings: ['0x' . str_repeat('bb', 32)],
                pathIndices: [5], // invalid: must be 0 or 1
            );

            expect($service->verifyCommitment('0x' . str_repeat('aa', 32), $path))->toBeFalse();
        });
    });

    describe('getMerklePath', function (): void {
        it('throws for invalid commitment format', function (): void {
            $service = new ProductionMerkleTreeService();
            $service->getMerklePath('invalid_commitment', 'polygon');
        })->throws(InvalidArgumentException::class);
    });

    describe('syncTree', function (): void {
        it('clears cache and refetches', function (): void {
            config(['privacy.merkle.pool_addresses.polygon' => '0xpoolcontract']);

            Http::fake([
                'polygon-rpc.com' => Http::sequence()
                    ->push(['jsonrpc' => '2.0', 'result' => '0x' . str_repeat('cc', 32), 'id' => 1])
                    ->push(['jsonrpc' => '2.0', 'result' => '0x05', 'id' => 1])
                    ->push(['jsonrpc' => '2.0', 'result' => '0x2000', 'id' => 1]),
            ]);

            $service = new ProductionMerkleTreeService();
            $root = $service->syncTree('polygon');

            expect($root->network)->toBe('polygon');
        });
    });
});
