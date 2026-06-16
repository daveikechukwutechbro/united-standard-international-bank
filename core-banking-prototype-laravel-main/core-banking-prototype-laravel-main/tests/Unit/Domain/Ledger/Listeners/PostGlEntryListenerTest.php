<?php

declare(strict_types=1);

use App\Domain\Ledger\Listeners\PostGlEntryListener;
use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Ledger\Services\PostingRuleEngine;

describe('PostGlEntryListener', function (): void {
    it('exists as a class', function (): void {
        expect(class_exists(PostGlEntryListener::class))->toBeTrue();
    });

    it('has a handle method', function (): void {
        $reflection = new ReflectionClass(PostGlEntryListener::class);
        expect($reflection->hasMethod('handle'))->toBeTrue();
    });

    it('requires LedgerService and PostingRuleEngine in constructor', function (): void {
        $reflection = new ReflectionClass(PostGlEntryListener::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor !== null);

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(2);

        $firstType = $params[0]->getType();
        assert($firstType instanceof ReflectionNamedType);
        expect($firstType->getName())->toBe(LedgerService::class);

        $secondType = $params[1]->getType();
        assert($secondType instanceof ReflectionNamedType);
        expect($secondType->getName())->toBe(PostingRuleEngine::class);
    });

    it('handle method accepts an object parameter', function (): void {
        $reflection = new ReflectionClass(PostGlEntryListener::class);
        $method = $reflection->getMethod('handle');
        $params = $method->getParameters();

        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('event');

        $type = $params[0]->getType();
        assert($type instanceof ReflectionNamedType);
        expect($type->getName())->toBe('object');
    });
});
