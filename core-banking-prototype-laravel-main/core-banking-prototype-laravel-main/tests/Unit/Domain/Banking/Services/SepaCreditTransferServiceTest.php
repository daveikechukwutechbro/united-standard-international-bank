<?php

declare(strict_types=1);

use App\Domain\Banking\Services\SepaCreditTransferService;

uses(Tests\TestCase::class);

it('SepaCreditTransferService class exists', function (): void {
    expect(class_exists(SepaCreditTransferService::class))->toBeTrue();
});

it('SepaCreditTransferService can be instantiated', function (): void {
    $service = new SepaCreditTransferService();
    expect($service)->toBeInstanceOf(SepaCreditTransferService::class);
});

it('SepaCreditTransferService has initiateTransfer method', function (): void {
    expect((new ReflectionClass(SepaCreditTransferService::class))->hasMethod('initiateTransfer'))->toBeTrue();
});

it('SepaCreditTransferService initiateTransfer has correct parameters', function (): void {
    $ref = new ReflectionMethod(SepaCreditTransferService::class, 'initiateTransfer');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(7);
    expect($params[0]->getName())->toBe('userId');
    $userIdType = $params[0]->getType();
    assert($userIdType instanceof ReflectionNamedType);
    expect($userIdType->getName())->toBe('int');
    expect($params[1]->getName())->toBe('creditorIban');
    expect($params[2]->getName())->toBe('creditorName');
    expect($params[3]->getName())->toBe('amount');
    expect($params[4]->getName())->toBe('currency');
    expect($params[5]->getName())->toBe('instant');
    expect($params[5]->isOptional())->toBeTrue();
    expect($params[6]->getName())->toBe('reference');
    expect($params[6]->isOptional())->toBeTrue();
});

it('SepaCreditTransferService has getTransferStatus method', function (): void {
    expect((new ReflectionClass(SepaCreditTransferService::class))->hasMethod('getTransferStatus'))->toBeTrue();
});

it('SepaCreditTransferService getTransferStatus accepts single string parameter', function (): void {
    $ref = new ReflectionMethod(SepaCreditTransferService::class, 'getTransferStatus');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(1);
    expect($params[0]->getName())->toBe('transferId');
    $transferIdType = $params[0]->getType();
    assert($transferIdType instanceof ReflectionNamedType);
    expect($transferIdType->getName())->toBe('string');
});

it('SepaCreditTransferService has cancelTransfer method', function (): void {
    expect((new ReflectionClass(SepaCreditTransferService::class))->hasMethod('cancelTransfer'))->toBeTrue();
});

it('SepaCreditTransferService cancelTransfer accepts single string parameter', function (): void {
    $ref = new ReflectionMethod(SepaCreditTransferService::class, 'cancelTransfer');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(1);
    expect($params[0]->getName())->toBe('transferId');
    $cancelTransferIdType = $params[0]->getType();
    assert($cancelTransferIdType instanceof ReflectionNamedType);
    expect($cancelTransferIdType->getName())->toBe('string');
});

it('SepaCreditTransferService initiateTransfer returns structured array for standard SCT', function (): void {
    $service = new SepaCreditTransferService();

    $result = $service->initiateTransfer(
        userId: 1,
        creditorIban: 'DE89370400440532013000',
        creditorName: 'Test GmbH',
        amount: '250.00',
        currency: 'EUR',
        instant: false,
        reference: 'INV-2026-001',
    );

    expect($result)->toHaveKey('transfer_id')
        ->toHaveKey('type')
        ->toHaveKey('status')
        ->toHaveKey('creditor_iban')
        ->toHaveKey('creditor_name')
        ->toHaveKey('amount')
        ->toHaveKey('currency')
        ->toHaveKey('reference')
        ->toHaveKey('instant')
        ->toHaveKey('iso20022_message')
        ->toHaveKey('iso20022_xml')
        ->toHaveKey('created_at')
        ->toHaveKey('estimated_settlement');

    expect($result['type'])->toBe('SEPA');
    expect($result['status'])->toBe('pending');
    expect($result['instant'])->toBeFalse();
    expect($result['transfer_id'])->toStartWith('SCT-');
    expect($result['iso20022_xml'])->toContain('pain.001.001.09');
});

it('SepaCreditTransferService initiateTransfer returns structured array for SCT Inst', function (): void {
    $service = new SepaCreditTransferService();

    $result = $service->initiateTransfer(
        userId: 1,
        creditorIban: 'DE89370400440532013000',
        creditorName: 'Test GmbH',
        amount: '100.00',
        currency: 'EUR',
        instant: true,
    );

    expect($result['type'])->toBe('SEPA_INSTANT');
    expect($result['instant'])->toBeTrue();
    expect($result['transfer_id'])->toStartWith('SCTINST-');
    expect($result['iso20022_xml'])->toContain('pacs.008.001.08');
});

it('SepaCreditTransferService getTransferStatus returns structured array', function (): void {
    $service = new SepaCreditTransferService();
    $result = $service->getTransferStatus('SCT-TEST-001');

    expect($result)->toHaveKey('transfer_id')
        ->toHaveKey('status')
        ->toHaveKey('checked_at');

    expect($result['transfer_id'])->toBe('SCT-TEST-001');
});

it('SepaCreditTransferService cancelTransfer returns structured array for non-instant', function (): void {
    $service = new SepaCreditTransferService();
    $result = $service->cancelTransfer('SCT-TEST-001');

    expect($result)->toHaveKey('transfer_id')
        ->toHaveKey('status')
        ->toHaveKey('cancelled_at');

    expect($result['status'])->toBe('cancelled');
});

it('SepaCreditTransferService cancelTransfer throws for instant transfers', function (): void {
    $service = new SepaCreditTransferService();

    expect(fn () => $service->cancelTransfer('SCTINST-ABC123'))
        ->toThrow(RuntimeException::class, 'Instant credit transfer');
});
