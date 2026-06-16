<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\ConsentManagementService;

describe('ConsentManagementService', function () {
    it('can be instantiated', function () {
        $service = new ConsentManagementService();
        expect($service)->toBeInstanceOf(ConsentManagementService::class);
    });

    it('returns demo consent status', function () {
        $service = new ConsentManagementService();
        $demo = $service->getDemoStatus();

        expect($demo)
            ->toHaveKey('marketing_emails')
            ->toHaveKey('analytics')
            ->toHaveKey('transaction_processing')
            ->toHaveKey('kyc_aml');
    });

    it('gets consent history for user', function () {
        $service = new ConsentManagementService();
        $history = $service->getConsentHistory('test-user-uuid');

        expect($history)->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    it('gets consent coverage stats', function () {
        $service = new ConsentManagementService();
        $stats = $service->getCoverageStats();

        expect($stats)->toBeArray();
    });
});
