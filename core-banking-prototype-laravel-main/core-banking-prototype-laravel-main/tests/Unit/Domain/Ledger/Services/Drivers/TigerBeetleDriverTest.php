<?php

declare(strict_types=1);

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Services\Drivers\TigerBeetleDriver;

uses(Tests\TestCase::class);

it('implements LedgerDriverInterface', function (): void {
    $driver = new TigerBeetleDriver();
    expect($driver)->toBeInstanceOf(LedgerDriverInterface::class);
});

it('has all required interface methods', function (): void {
    $reflection = new ReflectionClass(TigerBeetleDriver::class);

    expect($reflection->hasMethod('post'))->toBeTrue();
    expect($reflection->hasMethod('balance'))->toBeTrue();
    expect($reflection->hasMethod('trialBalance'))->toBeTrue();
    expect($reflection->hasMethod('accountHistory'))->toBeTrue();
});

it('returns zero balance when TigerBeetle is unreachable', function (): void {
    config(['ledger.tigerbeetle.addresses' => '127.0.0.1:19999']);
    $driver = new TigerBeetleDriver();

    $balance = $driver->balance('1100');
    expect($balance)->toHaveKeys(['amount', 'currency']);
    expect($balance['amount'])->toBe('0.0000');
});

it('returns empty trial balance', function (): void {
    $driver = new TigerBeetleDriver();
    $result = $driver->trialBalance();
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

it('returns empty collection for unreachable account history', function (): void {
    config(['ledger.tigerbeetle.addresses' => '127.0.0.1:19999']);
    $driver = new TigerBeetleDriver();

    $history = $driver->accountHistory('1100', now()->subDays(7), now());
    expect($history)->toBeEmpty();
});
