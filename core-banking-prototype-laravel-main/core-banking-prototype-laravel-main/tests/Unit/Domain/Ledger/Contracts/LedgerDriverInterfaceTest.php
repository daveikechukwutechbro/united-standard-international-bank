<?php

declare(strict_types=1);

use App\Domain\Ledger\Contracts\LedgerDriverInterface;

describe('LedgerDriverInterface', function (): void {
    it('exists as an interface', function (): void {
        expect(interface_exists(LedgerDriverInterface::class))->toBeTrue();
    });

    it('declares the post method', function (): void {
        $reflection = new ReflectionClass(LedgerDriverInterface::class);
        expect($reflection->hasMethod('post'))->toBeTrue();

        $method = $reflection->getMethod('post');
        expect($method->getParameters())->toHaveCount(1);
        expect($method->getParameters()[0]->getName())->toBe('entry');

        $returnType = $method->getReturnType();
        assert($returnType instanceof ReflectionNamedType);
        expect($returnType->getName())->toBe('void');
    });

    it('declares the balance method', function (): void {
        $reflection = new ReflectionClass(LedgerDriverInterface::class);
        expect($reflection->hasMethod('balance'))->toBeTrue();

        $method = $reflection->getMethod('balance');
        $params = $method->getParameters();
        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('accountCode');
        expect($params[1]->getName())->toBe('asOf');
        expect($params[1]->isOptional())->toBeTrue();
    });

    it('declares the trialBalance method', function (): void {
        $reflection = new ReflectionClass(LedgerDriverInterface::class);
        expect($reflection->hasMethod('trialBalance'))->toBeTrue();

        $method = $reflection->getMethod('trialBalance');
        $params = $method->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('asOf');
        expect($params[0]->isOptional())->toBeTrue();
    });

    it('declares the accountHistory method', function (): void {
        $reflection = new ReflectionClass(LedgerDriverInterface::class);
        expect($reflection->hasMethod('accountHistory'))->toBeTrue();

        $method = $reflection->getMethod('accountHistory');
        $params = $method->getParameters();
        expect($params)->toHaveCount(3);
        expect($params[0]->getName())->toBe('accountCode');
        expect($params[1]->getName())->toBe('from');
        expect($params[2]->getName())->toBe('to');
    });

    it('has exactly four methods', function (): void {
        $reflection = new ReflectionClass(LedgerDriverInterface::class);
        $methods = $reflection->getMethods();
        expect($methods)->toHaveCount(4);
    });
});
