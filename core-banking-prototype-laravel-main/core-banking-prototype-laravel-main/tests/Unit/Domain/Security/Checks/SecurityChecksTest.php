<?php

declare(strict_types=1);

use App\Domain\Security\Checks\AuthenticationCheck;
use App\Domain\Security\Checks\DependencyVulnerabilityCheck;
use App\Domain\Security\Checks\EncryptionCheck;
use App\Domain\Security\Checks\InputValidationCheck;
use App\Domain\Security\Checks\RateLimitingCheck;
use App\Domain\Security\Checks\SecurityHeadersCheck;
use App\Domain\Security\Checks\SensitiveDataExposureCheck;
use App\Domain\Security\Checks\SqlInjectionCheck;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

uses(Tests\TestCase::class);

describe('DependencyVulnerabilityCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new DependencyVulnerabilityCheck();
        expect($check->getName())->toBe('dependency_vulnerability');
        expect($check->getCategory())->toBe('A06: Vulnerable and Outdated Components');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
        expect($result->score)->toBeGreaterThanOrEqual(0);
        expect($result->score)->toBeLessThanOrEqual(100);
    });
});

describe('SecurityHeadersCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new SecurityHeadersCheck();
        expect($check->getName())->toBe('security_headers');
        expect($check->getCategory())->toBe('A05: Security Misconfiguration');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
    });

    it('detects SecurityHeaders middleware', function (): void {
        $check = new SecurityHeadersCheck();
        $result = $check->run();

        // Middleware exists in this project, so should get a positive score
        expect($result->score)->toBeGreaterThan(0);
    });
});

describe('SqlInjectionCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new SqlInjectionCheck();
        expect($check->getName())->toBe('sql_injection');
        expect($check->getCategory())->toBe('A03: Injection');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
        expect($result->score)->toBeGreaterThanOrEqual(0);
    });
});

describe('AuthenticationCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new AuthenticationCheck();
        expect($check->getName())->toBe('authentication');
        expect($check->getCategory())->toBe('A07: Identification and Authentication Failures');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
    });
});

describe('EncryptionCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new EncryptionCheck();
        expect($check->getName())->toBe('encryption');
        expect($check->getCategory())->toBe('A02: Cryptographic Failures');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
    });

    it('checks APP_KEY is set', function (): void {
        $check = new EncryptionCheck();
        $result = $check->run();

        // In test env, APP_KEY should be set
        expect($result->score)->toBeGreaterThan(0);
    });

    it('detects debug mode', function (): void {
        config(['app.debug' => true]);
        $check = new EncryptionCheck();
        $result = $check->run();

        $hasDebugFinding = false;
        foreach ($result->findings as $finding) {
            if (str_contains($finding, 'debug mode')) {
                $hasDebugFinding = true;

                break;
            }
        }

        expect($hasDebugFinding)->toBeTrue();
    });

    it('detects weak bcrypt rounds', function (): void {
        config(['hashing.bcrypt.rounds' => 4]);
        $check = new EncryptionCheck();
        $result = $check->run();

        $hasBcryptFinding = false;
        foreach ($result->findings as $finding) {
            if (str_contains($finding, 'Bcrypt rounds')) {
                $hasBcryptFinding = true;

                break;
            }
        }

        expect($hasBcryptFinding)->toBeTrue();
    });
});

describe('RateLimitingCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new RateLimitingCheck();
        expect($check->getName())->toBe('rate_limiting');
        expect($check->getCategory())->toBe('A04: Insecure Design');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
    });
});

describe('InputValidationCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new InputValidationCheck();
        expect($check->getName())->toBe('input_validation');
        expect($check->getCategory())->toBe('A03: Injection');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
        expect($result->score)->toBeGreaterThanOrEqual(0);
    });
});

describe('SensitiveDataExposureCheck', function (): void {
    it('returns a valid result', function (): void {
        $check = new SensitiveDataExposureCheck();
        expect($check->getName())->toBe('sensitive_data_exposure');
        expect($check->getCategory())->toBe('A02: Cryptographic Failures');

        $result = $check->run();
        expect($result)->toBeInstanceOf(SecurityCheckResult::class);
    });

    it('checks .gitignore exists', function (): void {
        $check = new SensitiveDataExposureCheck();
        $result = $check->run();

        // .gitignore should exist in this project
        $hasMissingGitignore = false;
        foreach ($result->findings as $finding) {
            if (str_contains($finding, '.gitignore file not found')) {
                $hasMissingGitignore = true;
            }
        }

        expect($hasMissingGitignore)->toBeFalse();
    });
});

describe('SecurityCheckResult', function (): void {
    it('serializes to array', function (): void {
        $result = new SecurityCheckResult(
            name: 'test_check',
            category: 'A01: Test',
            passed: true,
            score: 85,
            findings: ['finding1'],
            recommendations: ['rec1'],
            severity: 'medium',
        );

        $array = $result->toArray();
        expect($array['name'])->toBe('test_check');
        expect($array['category'])->toBe('A01: Test');
        expect($array['passed'])->toBeTrue();
        expect($array['score'])->toBe(85);
        expect($array['findings'])->toBe(['finding1']);
        expect($array['recommendations'])->toBe(['rec1']);
        expect($array['severity'])->toBe('medium');
    });
});
