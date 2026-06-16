<?php

declare(strict_types=1);

use App\Domain\PaymentRails\Services\FedwireService;
use Tests\TestCase;

uses(TestCase::class);

describe('FedwireService structural tests', function (): void {
    it('class exists', function (): void {
        expect(class_exists(FedwireService::class))->toBeTrue();
    });

    it('has a sendTransfer method', function (): void {
        expect((new ReflectionClass(FedwireService::class))->hasMethod('sendTransfer'))->toBeTrue();
    });

    it('sendTransfer has 7 parameters with last one nullable', function (): void {
        $reflection = new ReflectionMethod(FedwireService::class, 'sendTransfer');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(7);

        $lastParam = $params[6];
        expect($lastParam->allowsNull())->toBeTrue();
        expect($lastParam->isOptional())->toBeTrue();
        expect($lastParam->getDefaultValue())->toBeNull();
    });

    it('has a getTransferStatus method', function (): void {
        expect((new ReflectionClass(FedwireService::class))->hasMethod('getTransferStatus'))->toBeTrue();
    });

    it('getTransferStatus has 1 parameter', function (): void {
        $reflection = new ReflectionMethod(FedwireService::class, 'getTransferStatus');
        expect($reflection->getParameters())->toHaveCount(1);
    });

    it('has a processCallback method', function (): void {
        expect((new ReflectionClass(FedwireService::class))->hasMethod('processCallback'))->toBeTrue();
    });

    it('processCallback has 3 parameters', function (): void {
        $reflection = new ReflectionMethod(FedwireService::class, 'processCallback');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(3);
    });

    it('processCallback last parameter is nullable', function (): void {
        $reflection = new ReflectionMethod(FedwireService::class, 'processCallback');
        $params = $reflection->getParameters();
        $lastParam = $params[2];

        expect($lastParam->allowsNull())->toBeTrue();
        expect($lastParam->isOptional())->toBeTrue();
        expect($lastParam->getDefaultValue())->toBeNull();
    });
});
