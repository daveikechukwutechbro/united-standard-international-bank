<?php

declare(strict_types=1);

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Services\ReconciliationService;

uses(Tests\TestCase::class);

describe('ReconciliationService', function (): void {
    it('class exists', function (): void {
        expect(class_exists(ReconciliationService::class))->toBeTrue();
    });

    it('has reconcile method', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        expect($reflection->hasMethod('reconcile'))->toBeTrue();
    });

    it('has getHistory method', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        expect($reflection->hasMethod('getHistory'))->toBeTrue();
    });

    it('has resolveDiscrepancy method', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        expect($reflection->hasMethod('resolveDiscrepancy'))->toBeTrue();
    });

    it('reconcile has correct parameter count (5)', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        $method = $reflection->getMethod('reconcile');
        $params = $method->getParameters();

        expect($params)->toHaveCount(5);
        expect($params[0]->getName())->toBe('domain');
        expect($params[1]->getName())->toBe('glAccountCode');
        expect($params[2]->getName())->toBe('domainBalance');
        expect($params[3]->getName())->toBe('periodStart');
        expect($params[4]->getName())->toBe('periodEnd');
    });

    it('constructor requires LedgerDriverInterface', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor instanceof ReflectionMethod);

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('driver');

        $type = $params[0]->getType();
        assert($type instanceof ReflectionNamedType);
        expect($type->getName())->toBe(LedgerDriverInterface::class);
    });

    it('can be instantiated with a mock driver', function (): void {
        $driver = Mockery::mock(LedgerDriverInterface::class);
        $service = new ReconciliationService($driver);

        expect($service)->toBeInstanceOf(ReconciliationService::class);
    });

    it('getHistory has correct parameters with default limit', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        $method = $reflection->getMethod('getHistory');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('domain');
        expect($params[1]->getName())->toBe('limit');
        expect($params[1]->isOptional())->toBeTrue();
        expect($params[1]->getDefaultValue())->toBe(30);
    });

    it('resolveDiscrepancy has correct parameters', function (): void {
        $reflection = new ReflectionClass(ReconciliationService::class);
        $method = $reflection->getMethod('resolveDiscrepancy');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('reportId');
        expect($params[1]->getName())->toBe('notes');
    });
});
