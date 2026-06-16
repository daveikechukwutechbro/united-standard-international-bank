<?php

declare(strict_types=1);

use App\Domain\X402\Enums\AssetTransferMethod;
use App\Domain\X402\Enums\PaymentScheme;
use App\Domain\X402\Enums\SettlementStatus;
use App\Domain\X402\Enums\X402Network;

test('PaymentScheme has correct values', function () {
    expect(PaymentScheme::EXACT->value)->toBe('exact');
    expect(PaymentScheme::UPTO->value)->toBe('upto');
    expect(PaymentScheme::EXACT->label())->toBeString();
});

test('X402Network has expected networks', function () {
    expect(X402Network::BASE_MAINNET->value)->toBe('eip155:8453');
    expect(X402Network::BASE_SEPOLIA->value)->toBe('eip155:84532');
    expect(X402Network::ETHEREUM_MAINNET->value)->toBe('eip155:1');
});

test('X402Network identifies testnets', function () {
    expect(X402Network::BASE_MAINNET->isTestnet())->toBeFalse();
    expect(X402Network::BASE_SEPOLIA->isTestnet())->toBeTrue();
    expect(X402Network::ETHEREUM_MAINNET->isTestnet())->toBeFalse();
});

test('X402Network returns chain IDs', function () {
    expect(X402Network::BASE_MAINNET->chainId())->toBe(8453);
    expect(X402Network::BASE_SEPOLIA->chainId())->toBe(84532);
    expect(X402Network::ETHEREUM_MAINNET->chainId())->toBe(1);
});

test('X402Network returns USDC decimals as 6', function () {
    foreach (X402Network::cases() as $network) {
        expect($network->usdcDecimals())->toBe(6);
    }
});

test('X402Network has labels', function () {
    expect(X402Network::BASE_MAINNET->label())->toBeString()->not->toBeEmpty(); // @phpstan-ignore property.notFound
    expect(X402Network::BASE_SEPOLIA->label())->toBeString()->not->toBeEmpty(); // @phpstan-ignore property.notFound
});

test('SettlementStatus has expected statuses', function () {
    expect(SettlementStatus::PENDING->value)->toBe('pending');
    expect(SettlementStatus::VERIFIED->value)->toBe('verified');
    expect(SettlementStatus::SETTLED->value)->toBe('settled');
    expect(SettlementStatus::FAILED->value)->toBe('failed');
    expect(SettlementStatus::EXPIRED->value)->toBe('expired');
});

test('SettlementStatus identifies final states', function () {
    expect(SettlementStatus::PENDING->isFinal())->toBeFalse();
    expect(SettlementStatus::VERIFIED->isFinal())->toBeFalse();
    expect(SettlementStatus::SETTLED->isFinal())->toBeTrue();
    expect(SettlementStatus::FAILED->isFinal())->toBeTrue();
    expect(SettlementStatus::EXPIRED->isFinal())->toBeTrue();
});

test('AssetTransferMethod has correct values', function () {
    expect(AssetTransferMethod::EIP3009->value)->toBe('eip3009');
    expect(AssetTransferMethod::PERMIT2->value)->toBe('permit2');
});
