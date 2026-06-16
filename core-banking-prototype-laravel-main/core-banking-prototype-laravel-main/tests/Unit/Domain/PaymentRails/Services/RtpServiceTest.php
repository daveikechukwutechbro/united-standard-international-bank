<?php

declare(strict_types=1);

use App\Domain\PaymentRails\Services\RtpService;
use Tests\TestCase;

uses(TestCase::class);

describe('RtpService structural tests', function (): void {
    it('class exists', function (): void {
        expect(class_exists(RtpService::class))->toBeTrue();
    });

    it('has a sendPayment method', function (): void {
        expect((new ReflectionClass(RtpService::class))->hasMethod('sendPayment'))->toBeTrue();
    });

    it('sendPayment has 7 parameters with last one nullable', function (): void {
        $reflection = new ReflectionMethod(RtpService::class, 'sendPayment');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(7);

        $lastParam = $params[6];
        expect($lastParam->allowsNull())->toBeTrue();
        expect($lastParam->isOptional())->toBeTrue();
        expect($lastParam->getDefaultValue())->toBeNull();
    });

    it('has a requestPayment method', function (): void {
        expect((new ReflectionClass(RtpService::class))->hasMethod('requestPayment'))->toBeTrue();
    });

    it('requestPayment has 6 parameters with last one nullable', function (): void {
        $reflection = new ReflectionMethod(RtpService::class, 'requestPayment');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(6);

        $lastParam = $params[5];
        expect($lastParam->allowsNull())->toBeTrue();
        expect($lastParam->isOptional())->toBeTrue();
        expect($lastParam->getDefaultValue())->toBeNull();
    });

    it('has a getPaymentStatus method', function (): void {
        expect((new ReflectionClass(RtpService::class))->hasMethod('getPaymentStatus'))->toBeTrue();
    });

    it('getPaymentStatus has 1 parameter', function (): void {
        $reflection = new ReflectionMethod(RtpService::class, 'getPaymentStatus');
        expect($reflection->getParameters())->toHaveCount(1);
    });
});
