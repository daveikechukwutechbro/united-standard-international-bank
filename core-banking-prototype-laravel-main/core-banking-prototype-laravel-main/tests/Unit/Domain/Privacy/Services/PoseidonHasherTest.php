<?php

declare(strict_types=1);

use App\Domain\Privacy\Services\PoseidonHasher;

uses(Tests\TestCase::class);

describe('PoseidonHasher', function (): void {
    describe('hash with sha3-256 fallback', function (): void {
        it('produces deterministic hashes', function (): void {
            config(['privacy.merkle.hash_algorithm' => 'sha3-256']);
            $hasher = new PoseidonHasher();

            $left = '0x' . str_repeat('aa', 32);
            $right = '0x' . str_repeat('bb', 32);

            $hash1 = $hasher->hash($left, $right);
            $hash2 = $hasher->hash($left, $right);

            expect($hash1)->toBe($hash2);
            expect($hash1)->toStartWith('0x');
        });

        it('produces different hashes for different inputs', function (): void {
            config(['privacy.merkle.hash_algorithm' => 'sha3-256']);
            $hasher = new PoseidonHasher();

            $a = '0x' . str_repeat('aa', 32);
            $b = '0x' . str_repeat('bb', 32);
            $c = '0x' . str_repeat('cc', 32);

            $hash1 = $hasher->hash($a, $b);
            $hash2 = $hasher->hash($a, $c);

            expect($hash1)->not->toBe($hash2);
        });

        it('is commutative (sorts inputs)', function (): void {
            config(['privacy.merkle.hash_algorithm' => 'sha3-256']);
            $hasher = new PoseidonHasher();

            $left = '0x' . str_repeat('aa', 32);
            $right = '0x' . str_repeat('bb', 32);

            $hash1 = $hasher->hash($left, $right);
            $hash2 = $hasher->hash($right, $left);

            expect($hash1)->toBe($hash2);
        });

        it('returns 0x-prefixed hex string', function (): void {
            config(['privacy.merkle.hash_algorithm' => 'sha3-256']);
            $hasher = new PoseidonHasher();

            $result = $hasher->hash(
                '0x' . str_repeat('11', 32),
                '0x' . str_repeat('22', 32),
            );

            expect($result)->toStartWith('0x');
            expect(strlen($result))->toBe(66); // 0x + 64 hex chars = 32 bytes
        });
    });

    describe('isAvailable', function (): void {
        it('returns a boolean', function (): void {
            $hasher = new PoseidonHasher();
            $result = $hasher->isAvailable();
            expect($result)->toBeBool();
        });

        it('caches the availability check', function (): void {
            $hasher = new PoseidonHasher();
            $result1 = $hasher->isAvailable();
            $result2 = $hasher->isAvailable();

            expect($result1)->toBe($result2);
        });
    });

    describe('poseidon mode (with sha3 fallback)', function (): void {
        it('falls back to sha3-256 when poseidon unavailable', function (): void {
            config(['privacy.merkle.hash_algorithm' => 'poseidon']);
            $hasher = new PoseidonHasher();

            // Even with poseidon configured, if Node.js isn't available it falls back
            $result = $hasher->hash(
                '0x' . str_repeat('aa', 32),
                '0x' . str_repeat('bb', 32),
            );

            expect($result)->toStartWith('0x');
            expect(strlen($result))->toBeGreaterThanOrEqual(66);
        });
    });
});
