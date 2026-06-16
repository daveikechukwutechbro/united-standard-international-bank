<?php

declare(strict_types=1);

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Services\LedgerService;

describe('LedgerService', function (): void {
    it('exists as a class', function (): void {
        expect(class_exists(LedgerService::class))->toBeTrue();
    });

    it('requires LedgerDriverInterface in constructor', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor !== null);

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(1);

        $param = $params[0];
        $type = $param->getType();
        assert($type instanceof ReflectionNamedType);
        expect($type->getName())->toBe(LedgerDriverInterface::class);
    });

    it('declares the post method with correct signature', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->hasMethod('post'))->toBeTrue();

        $method = $reflection->getMethod('post');
        $params = $method->getParameters();

        // description, lines, sourceDomain=null, sourceEventId=null
        expect($params)->toHaveCount(4);
        expect($params[0]->getName())->toBe('description');
        expect($params[1]->getName())->toBe('lines');
        expect($params[2]->getName())->toBe('sourceDomain');
        expect($params[2]->isOptional())->toBeTrue();
        expect($params[3]->getName())->toBe('sourceEventId');
        expect($params[3]->isOptional())->toBeTrue();

        $returnType = $method->getReturnType();
        assert($returnType instanceof ReflectionNamedType);
        expect($returnType->getName())->toBe(App\Domain\Ledger\Models\JournalEntry::class);
    });

    it('declares the reverse method with 2 string parameters', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->hasMethod('reverse'))->toBeTrue();

        $method = $reflection->getMethod('reverse');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('entryId');
        expect($params[1]->getName())->toBe('reason');

        $type0 = $params[0]->getType();
        $type1 = $params[1]->getType();
        assert($type0 instanceof ReflectionNamedType);
        assert($type1 instanceof ReflectionNamedType);
        expect($type0->getName())->toBe('string');
        expect($type1->getName())->toBe('string');
    });

    it('declares the getBalance method', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->hasMethod('getBalance'))->toBeTrue();

        $method = $reflection->getMethod('getBalance');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('accountCode');
        expect($params[1]->getName())->toBe('asOf');
        expect($params[1]->isOptional())->toBeTrue();
    });

    it('declares the getTrialBalance method', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->hasMethod('getTrialBalance'))->toBeTrue();

        $method = $reflection->getMethod('getTrialBalance');
        $params = $method->getParameters();

        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('asOf');
        expect($params[0]->isOptional())->toBeTrue();
    });

    it('declares the getAccountHistory method', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->hasMethod('getAccountHistory'))->toBeTrue();

        $method = $reflection->getMethod('getAccountHistory');
        $params = $method->getParameters();

        expect($params)->toHaveCount(3);
        expect($params[0]->getName())->toBe('accountCode');
        expect($params[1]->getName())->toBe('from');
        expect($params[2]->getName())->toBe('to');
    });

    it('is declared as final', function (): void {
        $reflection = new ReflectionClass(LedgerService::class);
        expect($reflection->isFinal())->toBeTrue();
    });
});
