<?php

declare(strict_types=1);

use App\Domain\Banking\Services\SepaDirectDebitService;
use App\Domain\Banking\Services\SepaMandateService;

uses(Tests\TestCase::class);

it('SepaDirectDebitService class exists', function (): void {
    expect(class_exists(SepaDirectDebitService::class))->toBeTrue();
});

it('SepaDirectDebitService can be instantiated with SepaMandateService', function (): void {
    $mandateService = new SepaMandateService();
    $service = new SepaDirectDebitService($mandateService);

    expect($service)->toBeInstanceOf(SepaDirectDebitService::class);
});

it('SepaDirectDebitService has createCollection method', function (): void {
    expect((new ReflectionClass(SepaDirectDebitService::class))->hasMethod('createCollection'))->toBeTrue();
});

it('SepaDirectDebitService createCollection has correct parameter count', function (): void {
    $ref = new ReflectionMethod(SepaDirectDebitService::class, 'createCollection');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(4);
    expect($params[0]->getName())->toBe('mandateId');
    expect($params[1]->getName())->toBe('amount');
    expect($params[2]->getName())->toBe('currency');
    expect($params[3]->getName())->toBe('reference');
    expect($params[3]->isOptional())->toBeTrue();
});

it('SepaDirectDebitService has submitCollection method', function (): void {
    expect((new ReflectionClass(SepaDirectDebitService::class))->hasMethod('submitCollection'))->toBeTrue();
});

it('SepaDirectDebitService submitCollection accepts single string parameter', function (): void {
    $ref = new ReflectionMethod(SepaDirectDebitService::class, 'submitCollection');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(1);
    expect($params[0]->getName())->toBe('collectionId');
    $collectionIdType = $params[0]->getType();
    assert($collectionIdType instanceof ReflectionNamedType);
    expect($collectionIdType->getName())->toBe('string');
});

it('SepaDirectDebitService has processReturn method', function (): void {
    expect((new ReflectionClass(SepaDirectDebitService::class))->hasMethod('processReturn'))->toBeTrue();
});

it('SepaDirectDebitService processReturn has correct parameter count', function (): void {
    $ref = new ReflectionMethod(SepaDirectDebitService::class, 'processReturn');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(3);
    expect($params[0]->getName())->toBe('collectionId');
    expect($params[1]->getName())->toBe('returnCode');
    expect($params[2]->getName())->toBe('returnReason');
});

it('SepaDirectDebitService has getCollectionsByMandate method', function (): void {
    expect((new ReflectionClass(SepaDirectDebitService::class))->hasMethod('getCollectionsByMandate'))->toBeTrue();
});

it('SepaDirectDebitService getCollectionsByMandate returns array', function (): void {
    $ref = new ReflectionMethod(SepaDirectDebitService::class, 'getCollectionsByMandate');
    $returnType = $ref->getReturnType();
    assert($returnType instanceof ReflectionNamedType);
    expect($returnType->getName())->toBe('array');
});

it('SepaDirectDebitService submitCollection returns structured array', function (): void {
    $mandateService = new SepaMandateService();
    $service = new SepaDirectDebitService($mandateService);

    $result = $service->submitCollection('SDD-TEST-001');

    expect($result)->toHaveKey('collection_id')
        ->toHaveKey('status')
        ->toHaveKey('submitted_at')
        ->toHaveKey('custodian_reference')
        ->toHaveKey('estimated_settlement');

    expect($result['collection_id'])->toBe('SDD-TEST-001');
    expect($result['status'])->toBe('submitted');
});

it('SepaDirectDebitService processReturn returns structured array', function (): void {
    $mandateService = new SepaMandateService();
    $service = new SepaDirectDebitService($mandateService);

    $result = $service->processReturn('SDD-TEST-001', 'AM04', 'InsufficientFunds');

    expect($result)->toHaveKey('collection_id')
        ->toHaveKey('status')
        ->toHaveKey('return_code')
        ->toHaveKey('return_reason')
        ->toHaveKey('processed_at');

    expect($result['status'])->toBe('returned');
    expect($result['return_code'])->toBe('AM04');
});

it('SepaDirectDebitService getCollectionsByMandate returns empty array for new mandate', function (): void {
    $mandateService = new SepaMandateService();
    $service = new SepaDirectDebitService($mandateService);

    $result = $service->getCollectionsByMandate('MNDT-NONEXISTENT');

    expect($result)->toBeArray()->toBeEmpty();
});
