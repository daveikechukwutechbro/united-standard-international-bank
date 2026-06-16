<?php

declare(strict_types=1);

use App\Domain\ISO20022\ValueObjects\Pacs002;
use App\Domain\ISO20022\ValueObjects\Pacs008;
use App\Domain\PaymentRails\Services\FedNowService;
use Tests\TestCase;

uses(TestCase::class);

describe('FedNowService structural tests', function (): void {
    it('class exists', function (): void {
        expect(class_exists(FedNowService::class))->toBeTrue();
    });

    it('has a sendInstantPayment method', function (): void {
        expect((new ReflectionClass(FedNowService::class))->hasMethod('sendInstantPayment'))->toBeTrue();
    });

    it('sendInstantPayment has 7 parameters with last one nullable', function (): void {
        $reflection = new ReflectionMethod(FedNowService::class, 'sendInstantPayment');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(7);

        $lastParam = $params[6];
        expect($lastParam->allowsNull())->toBeTrue();
        expect($lastParam->isOptional())->toBeTrue();
        expect($lastParam->getDefaultValue())->toBeNull();
    });

    it('has a processStatusReport method', function (): void {
        expect((new ReflectionClass(FedNowService::class))->hasMethod('processStatusReport'))->toBeTrue();
    });

    it('processStatusReport has 1 parameter', function (): void {
        $reflection = new ReflectionMethod(FedNowService::class, 'processStatusReport');
        expect($reflection->getParameters())->toHaveCount(1);
    });

    it('has a getPaymentStatus method', function (): void {
        expect((new ReflectionClass(FedNowService::class))->hasMethod('getPaymentStatus'))->toBeTrue();
    });

    it('constructor accepts Pacs008 and Pacs002 dependencies', function (): void {
        $reflection = new ReflectionClass(FedNowService::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        assert($constructor instanceof ReflectionMethod);

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(2);

        $type0 = $params[0]->getType();
        assert($type0 instanceof ReflectionNamedType);
        expect($type0->getName())->toBe(Pacs008::class);

        $type1 = $params[1]->getType();
        assert($type1 instanceof ReflectionNamedType);
        expect($type1->getName())->toBe(Pacs002::class);
    });

    it('can be instantiated with concrete Pacs008 and Pacs002 instances', function (): void {
        $pacs008 = new Pacs008(
            messageId: 'TEST-001',
            creationDateTime: new DateTimeImmutable(),
            numberOfTransactions: 1,
            settlementMethod: 'CLRG',
            instructingAgentBic: 'FNAEGISUS',
            instructedAgentBic: 'TESTBIC0',
            endToEndId: 'E2E-001',
            uetr: '550e8400-e29b-41d4-a716-446655440000',
            amount: '100.00',
            currency: 'USD',
            debtorName: 'Test Debtor',
            debtorIban: 'US12345678901234567890',
            creditorName: 'Test Creditor',
            creditorIban: 'US09876543210987654321',
        );

        $pacs002 = new Pacs002(
            messageId: 'STATUS-001',
            creationDateTime: new DateTimeImmutable(),
            originalMessageId: 'TEST-001',
            originalMessageType: 'pacs.008.001.08',
            groupStatus: 'ACSC',
            transactionStatuses: [],
        );

        $service = new FedNowService($pacs008, $pacs002);

        expect($service)->toBeInstanceOf(FedNowService::class);
    });
});
