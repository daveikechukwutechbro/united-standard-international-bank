<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Asset\Aggregates\AssetTransferAggregate;
use App\Domain\Asset\Events\AssetTransferCompleted;
use App\Domain\Asset\Events\AssetTransferFailed;
use App\Domain\Asset\Events\AssetTransferInitiated;

it('can initiate asset transfer', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $fromAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $toAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $fromAmount = new Money(10000);
    $toAmount = new Money(8500);

    $aggregate = AssetTransferAggregate::retrieve($uuid)
        ->initiate(
            $fromAccount,
            $toAccount,
            'USD',
            'EUR',
            $fromAmount,
            $toAmount,
            0.85,
            'Test cross-asset transfer'
        );

    $events = $aggregate->getRecordedEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(AssetTransferInitiated::class);
    expect($events[0]->fromAccountUuid)->toEqual($fromAccount);
    expect($events[0]->toAccountUuid)->toEqual($toAccount);
    expect($events[0]->fromAssetCode)->toBe('USD');
    expect($events[0]->toAssetCode)->toBe('EUR');
    expect($events[0]->isCrossAssetTransfer())->toBeTrue();
    expect($events[0]->isSameAssetTransfer())->toBeFalse();
});

it('can complete asset transfer', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $fromAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $toAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $fromAmount = new Money(5000);
    $toAmount = new Money(5000);

    $aggregate = AssetTransferAggregate::retrieve($uuid)
        ->initiate($fromAccount, $toAccount, 'USD', 'USD', $fromAmount, $toAmount)
        ->complete('transfer-123');

    $events = $aggregate->getRecordedEvents();

    expect($events)->toHaveCount(2);
    expect($events[0])->toBeInstanceOf(AssetTransferInitiated::class);
    expect($events[1])->toBeInstanceOf(AssetTransferCompleted::class);
    expect($aggregate->getStatus())->toBe('completed');
});

it('can fail asset transfer', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $fromAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $toAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $fromAmount = new Money(5000);
    $toAmount = new Money(5000);

    $aggregate = AssetTransferAggregate::retrieve($uuid)
        ->initiate($fromAccount, $toAccount, 'USD', 'EUR', $fromAmount, $toAmount)
        ->fail('Insufficient balance', 'transfer-456');

    $events = $aggregate->getRecordedEvents();

    expect($events)->toHaveCount(2);
    expect($events[0])->toBeInstanceOf(AssetTransferInitiated::class);
    expect($events[1])->toBeInstanceOf(AssetTransferFailed::class);
    expect($aggregate->getStatus())->toBe('failed');
    expect($aggregate->getFailureReason())->toBe('Insufficient balance');
});

it('throws exception when completing uninitialized transfer', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();

    expect(fn () => AssetTransferAggregate::retrieve($uuid)->complete())
        ->toThrow(InvalidArgumentException::class, 'Transfer must be initiated before it can be completed');
});

it('throws exception when failing uninitialized transfer', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();

    expect(fn () => AssetTransferAggregate::retrieve($uuid)->fail('Test reason'))
        ->toThrow(InvalidArgumentException::class, 'Transfer must be initiated before it can fail');
});

it('can identify same asset vs cross asset transfers', function () {
    $uuid1 = (string) Illuminate\Support\Str::uuid();
    $uuid2 = (string) Illuminate\Support\Str::uuid();
    $fromAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $toAccount = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $amount = new Money(1000);

    // Same asset transfer
    $sameAssetAggregate = AssetTransferAggregate::retrieve($uuid1)
        ->initiate($fromAccount, $toAccount, 'USD', 'USD', $amount, $amount);

    // Cross asset transfer
    $crossAssetAggregate = AssetTransferAggregate::retrieve($uuid2)
        ->initiate($fromAccount, $toAccount, 'USD', 'EUR', $amount, new Money(850));

    expect($sameAssetAggregate->isSameAssetTransfer())->toBeTrue();
    expect($sameAssetAggregate->isCrossAssetTransfer())->toBeFalse();
    expect($crossAssetAggregate->isSameAssetTransfer())->toBeFalse();
    expect($crossAssetAggregate->isCrossAssetTransfer())->toBeTrue();
});
