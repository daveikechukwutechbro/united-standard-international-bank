<?php

declare(strict_types=1);

use App\Domain\Wallet\Helpers\SolanaAddressHelper;

uses(Tests\TestCase::class);

test('deriveAddress produces valid Base58 Solana address', function () {
    $address = SolanaAddressHelper::deriveAddress('test-seed');

    expect($address)->toMatch('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/');
});

test('deriveAddress is deterministic', function () {
    $a = SolanaAddressHelper::deriveAddress('user:42');
    $b = SolanaAddressHelper::deriveAddress('user:42');

    expect($a)->toBe($b);
});

test('deriveAddress produces different addresses for different seeds', function () {
    $a = SolanaAddressHelper::deriveAddress('user:1');
    $b = SolanaAddressHelper::deriveAddress('user:2');

    expect($a)->not->toBe($b);
});

test('deriveForUser produces same address as manual seed construction', function () {
    $fromHelper = SolanaAddressHelper::deriveForUser(42, 'test-app-key');
    $fromManual = SolanaAddressHelper::deriveAddress('solana:42:test-app-key');

    expect($fromHelper)->toBe($fromManual);
});

test('deriveForUser is deterministic across calls', function () {
    $a = SolanaAddressHelper::deriveForUser(1, 'key');
    $b = SolanaAddressHelper::deriveForUser(1, 'key');

    expect($a)->toBe($b);
});

test('deriveForUser produces different addresses for different users', function () {
    $a = SolanaAddressHelper::deriveForUser(1, 'key');
    $b = SolanaAddressHelper::deriveForUser(2, 'key');

    expect($a)->not->toBe($b);
});

test('base58Encode handles leading zero bytes', function () {
    $bytes = "\x00\x00" . random_bytes(30);
    $encoded = SolanaAddressHelper::base58Encode($bytes);

    expect($encoded)->toStartWith('11');
});

test('base58Encode returns empty string for empty input', function () {
    expect(SolanaAddressHelper::base58Encode(''))->toBe('');
});

test('isValid accepts real derived address', function () {
    $address = SolanaAddressHelper::deriveAddress('validation-test');

    expect(SolanaAddressHelper::isValid($address))->toBeTrue();
});

test('isValid rejects Ethereum address', function () {
    expect(SolanaAddressHelper::isValid('0x1234567890abcdef1234567890abcdef12345678'))->toBeFalse();
});

test('isValid rejects empty string', function () {
    expect(SolanaAddressHelper::isValid(''))->toBeFalse();
});

test('derived address is correct length for ed25519 public key', function () {
    $address = SolanaAddressHelper::deriveAddress('length-test');

    // ed25519 public key is 32 bytes → Base58 encoded is 43-44 chars
    expect(strlen($address))->toBeGreaterThanOrEqual(32)
        ->toBeLessThanOrEqual(44);
});
