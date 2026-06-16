<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Asset\Aggregates\AssetTransactionAggregate;
use App\Domain\Asset\Events\AssetTransactionCreated;

it('can create credit transaction', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $accountUuid = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $money = new Money(5000);

    $aggregate = AssetTransactionAggregate::retrieve($uuid)
        ->credit($accountUuid, 'USD', $money, 'Test deposit');

    $events = $aggregate->getRecordedEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(AssetTransactionCreated::class);
    expect($events[0]->accountUuid)->toEqual($accountUuid);
    expect($events[0]->assetCode)->toBe('USD');
    expect($events[0]->money)->toEqual($money);
    expect($events[0]->type)->toBe('credit');
    expect($events[0]->isCredit())->toBeTrue();
    expect($events[0]->isDebit())->toBeFalse();
});

it('can create debit transaction', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $accountUuid = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $money = new Money(3000);

    $aggregate = AssetTransactionAggregate::retrieve($uuid)
        ->debit($accountUuid, 'EUR', $money, 'Test withdrawal');

    $events = $aggregate->getRecordedEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(AssetTransactionCreated::class);
    expect($events[0]->accountUuid)->toEqual($accountUuid);
    expect($events[0]->assetCode)->toBe('EUR');
    expect($events[0]->money)->toEqual($money);
    expect($events[0]->type)->toBe('debit');
    expect($events[0]->isCredit())->toBeFalse();
    expect($events[0]->isDebit())->toBeTrue();
});

it('can apply asset transaction created event', function () {
    $uuid = (string) Illuminate\Support\Str::uuid();
    $accountUuid = new AccountUuid((string) Illuminate\Support\Str::uuid());
    $money = new Money(2500);

    $aggregate = AssetTransactionAggregate::retrieve($uuid)
        ->credit($accountUuid, 'BTC', $money);

    expect($aggregate->getAccountUuid())->toEqual($accountUuid);
    expect($aggregate->getAssetCode())->toBe('BTC');
    expect($aggregate->getMoney())->toEqual($money);
    expect($aggregate->getType())->toBe('credit');
    expect($aggregate->isCredit())->toBeTrue();
    expect($aggregate->getHash())->not->toBeNull();
});
