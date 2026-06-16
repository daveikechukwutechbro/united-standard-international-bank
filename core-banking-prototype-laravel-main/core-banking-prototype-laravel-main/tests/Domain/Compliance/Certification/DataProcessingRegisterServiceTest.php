<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\DataProcessingRegisterService;

describe('DataProcessingRegisterService', function () {
    it('can create a processing activity', function () {
        $service = new DataProcessingRegisterService();
        expect($service)->toBeInstanceOf(DataProcessingRegisterService::class);
    });

    it('returns demo register data', function () {
        $service = new DataProcessingRegisterService();
        $demo = $service->getDemoRegister();

        expect($demo)
            ->toHaveKey('register_name')
            ->toHaveKey('total_activities')
            ->and($demo['total_activities'])->toBe(5);
    });

    it('checks register completeness', function () {
        $service = new DataProcessingRegisterService();
        $completeness = $service->checkCompleteness();

        expect($completeness)
            ->toHaveKey('total')
            ->toHaveKey('complete')
            ->toHaveKey('incomplete')
            ->toHaveKey('completeness_rate');
    });

    it('exports the register', function () {
        $service = new DataProcessingRegisterService();
        $register = $service->exportRegister();

        expect($register)
            ->toHaveKey('register_name')
            ->toHaveKey('controller')
            ->toHaveKey('activities');
    });
});
