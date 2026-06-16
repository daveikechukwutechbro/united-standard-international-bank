<?php

declare(strict_types=1);

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Services\Drivers\EloquentDriver;

uses(Tests\TestCase::class);

describe('EloquentDriver', function (): void {
    it('implements LedgerDriverInterface', function (): void {
        $driver = new EloquentDriver();
        expect($driver)->toBeInstanceOf(LedgerDriverInterface::class);
    });

    it('has all required interface methods', function (): void {
        $reflection = new ReflectionClass(EloquentDriver::class);

        expect($reflection->hasMethod('post'))->toBeTrue();
        expect($reflection->hasMethod('balance'))->toBeTrue();
        expect($reflection->hasMethod('trialBalance'))->toBeTrue();
        expect($reflection->hasMethod('accountHistory'))->toBeTrue();
    });

    it('post() is public', function (): void {
        $reflection = new ReflectionClass(EloquentDriver::class);
        $method = $reflection->getMethod('post');

        expect($method->isPublic())->toBeTrue();
    });

    it('balance() is public and returns array', function (): void {
        $reflection = new ReflectionClass(EloquentDriver::class);
        $method = $reflection->getMethod('balance');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(2);
        expect($method->getParameters()[0]->getName())->toBe('accountCode');
        expect($method->getParameters()[1]->getName())->toBe('asOf');
        expect($method->getParameters()[1]->isOptional())->toBeTrue();
    });

    it('trialBalance() is public with optional asOf parameter', function (): void {
        $reflection = new ReflectionClass(EloquentDriver::class);
        $method = $reflection->getMethod('trialBalance');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(1);
        expect($method->getParameters()[0]->isOptional())->toBeTrue();
    });

    it('accountHistory() is public with three parameters', function (): void {
        $reflection = new ReflectionClass(EloquentDriver::class);
        $method = $reflection->getMethod('accountHistory');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(3);
        expect($method->getParameters()[0]->getName())->toBe('accountCode');
        expect($method->getParameters()[1]->getName())->toBe('from');
        expect($method->getParameters()[2]->getName())->toBe('to');
    });
});
