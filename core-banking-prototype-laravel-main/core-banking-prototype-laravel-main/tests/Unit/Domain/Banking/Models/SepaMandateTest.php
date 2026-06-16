<?php

declare(strict_types=1);

use App\Domain\Banking\Models\SepaMandate;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

uses(Tests\TestCase::class);

it('SepaMandate class exists', function (): void {
    expect(class_exists(SepaMandate::class))->toBeTrue();
});

it('SepaMandate uses HasUuids trait', function (): void {
    $traits = class_uses_recursive(SepaMandate::class);
    expect($traits)->toHaveKey(HasUuids::class);
});

it('SepaMandate uses SoftDeletes trait', function (): void {
    $traits = class_uses_recursive(SepaMandate::class);
    expect($traits)->toHaveKey(SoftDeletes::class);
});

it('SepaMandate has correct table name', function (): void {
    $model = new SepaMandate();
    expect($model->getTable())->toBe('sepa_mandates');
});

it('SepaMandate has isActive method', function (): void {
    expect((new ReflectionClass(SepaMandate::class))->hasMethod('isActive'))->toBeTrue();
});

it('SepaMandate has isExpired method', function (): void {
    expect((new ReflectionClass(SepaMandate::class))->hasMethod('isExpired'))->toBeTrue();
});

it('SepaMandate has scopeActive method', function (): void {
    expect((new ReflectionClass(SepaMandate::class))->hasMethod('scopeActive'))->toBeTrue();
});

it('SepaMandate has scopeForUser method', function (): void {
    expect((new ReflectionClass(SepaMandate::class))->hasMethod('scopeForUser'))->toBeTrue();
});

it('SepaMandate has scopeByScheme method', function (): void {
    expect((new ReflectionClass(SepaMandate::class))->hasMethod('scopeByScheme'))->toBeTrue();
});

it('SepaMandate scopeForUser accepts int userId', function (): void {
    $ref = new ReflectionMethod(SepaMandate::class, 'scopeForUser');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(2);
    expect($params[1]->getName())->toBe('userId');
    $userIdType = $params[1]->getType();
    assert($userIdType instanceof ReflectionNamedType);
    expect($userIdType->getName())->toBe('int');
});

it('SepaMandate scopeByScheme accepts string scheme', function (): void {
    $ref = new ReflectionMethod(SepaMandate::class, 'scopeByScheme');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(2);
    expect($params[1]->getName())->toBe('scheme');
    $schemeType = $params[1]->getType();
    assert($schemeType instanceof ReflectionNamedType);
    expect($schemeType->getName())->toBe('string');
});

it('SepaMandate isActive returns bool for active status', function (): void {
    $mandate = new SepaMandate(['status' => 'active']);
    expect($mandate->isActive())->toBeTrue();
});

it('SepaMandate isActive returns false for non-active status', function (): void {
    $mandate = new SepaMandate(['status' => 'suspended']);
    expect($mandate->isActive())->toBeFalse();
});
