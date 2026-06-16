<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\EncryptionVerificationService;

describe('EncryptionVerificationService', function () {
    it('runs full verification suite', function () {
        $service = new EncryptionVerificationService();
        $results = $service->runVerification();

        expect($results)->toHaveKey('at_rest')
            ->and($results)->toHaveKey('in_transit')
            ->and($results)->toHaveKey('key_strength')
            ->and($results)->toHaveKey('algorithm_compliance')
            ->and($results)->toHaveKey('summary')
            ->and($results['summary'])->toHaveKey('total_checks')
            ->and($results['summary']['total_checks'])->toBeGreaterThan(0);
    });

    it('verifies at-rest encryption', function () {
        $service = new EncryptionVerificationService();
        $result = $service->verifyAtRest();

        expect($result)->toHaveKey('category')
            ->and($result['category'])->toBe('Encryption at Rest')
            ->and($result)->toHaveKey('checks')
            ->and($result['checks'])->toBeArray();
    });

    it('verifies in-transit encryption', function () {
        $service = new EncryptionVerificationService();
        $result = $service->verifyInTransit();

        expect($result)->toHaveKey('category')
            ->and($result['category'])->toBe('Encryption in Transit')
            ->and($result)->toHaveKey('checks');
    });

    it('verifies key strength', function () {
        $service = new EncryptionVerificationService();
        $result = $service->verifyKeyStrength();

        expect($result)->toHaveKey('category')
            ->and($result['category'])->toBe('Key Strength')
            ->and($result)->toHaveKey('checks');
    });

    it('verifies algorithm compliance', function () {
        $service = new EncryptionVerificationService();
        $result = $service->verifyAlgorithms();

        expect($result)->toHaveKey('category')
            ->and($result['category'])->toBe('Algorithm Compliance');
    });

    it('returns demo results', function () {
        $service = new EncryptionVerificationService();
        $results = $service->getDemoResults();

        expect($results['summary']['total_checks'])->toBe(13)
            ->and($results['summary']['score'])->toBe(100.0);
    });
});
