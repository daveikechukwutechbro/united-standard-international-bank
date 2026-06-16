<?php

declare(strict_types=1);

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\Services\SecurityAuditService;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

uses(Tests\TestCase::class);

describe('SecurityAuditService', function (): void {
    describe('getAvailableChecks', function (): void {
        it('returns all default check names', function (): void {
            $service = new SecurityAuditService();
            $checks = $service->getAvailableChecks();

            expect($checks)->toContain('dependency_vulnerability');
            expect($checks)->toContain('security_headers');
            expect($checks)->toContain('sql_injection');
            expect($checks)->toContain('authentication');
            expect($checks)->toContain('encryption');
            expect($checks)->toContain('rate_limiting');
            expect($checks)->toContain('input_validation');
            expect($checks)->toContain('sensitive_data_exposure');
            expect($checks)->toHaveCount(10);
        });
    });

    describe('runCheck', function (): void {
        it('runs a specific check by name', function (): void {
            $service = new SecurityAuditService();
            $result = $service->runCheck('encryption');

            expect($result)->toBeInstanceOf(SecurityCheckResult::class);
            expect($result->name)->toBe('encryption');
            expect($result->category)->toBe('A02: Cryptographic Failures');
        });

        it('returns null for unknown check', function (): void {
            $service = new SecurityAuditService();
            expect($service->runCheck('nonexistent'))->toBeNull();
        });
    });

    describe('runFullAudit', function (): void {
        it('returns an audit report with all checks', function (): void {
            $service = new SecurityAuditService();
            $report = $service->runFullAudit();

            expect($report->checks)->toHaveCount(10);
            expect($report->overallScore)->toBeGreaterThanOrEqual(0);
            expect($report->overallScore)->toBeLessThanOrEqual(100);
            expect($report->grade)->toBeIn(['A', 'B', 'C', 'D', 'F']);
            expect($report->timestamp)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('registerCheck', function (): void {
        it('registers a custom check', function (): void {
            $service = new SecurityAuditService();

            $customCheck = Mockery::mock(SecurityCheckInterface::class);
            $customCheck->shouldReceive('getName')->andReturn('custom_check');
            $customCheck->shouldReceive('run')->andReturn(new SecurityCheckResult(
                name: 'custom_check',
                category: 'Custom',
                passed: true,
                score: 100,
            ));

            $service->registerCheck($customCheck);
            expect($service->getAvailableChecks())->toContain('custom_check');

            $result = $service->runCheck('custom_check');
            expect($result->name)->toBe('custom_check');
            expect($result->passed)->toBeTrue();
        });
    });

    describe('generateReport', function (): void {
        it('generates text report', function (): void {
            $service = new SecurityAuditService();
            $report = $service->runFullAudit();
            $text = $service->generateReport($report, 'text');

            expect($text)->toContain('FinAegis Security Audit Report');
            expect($text)->toContain('Overall Score');
        });

        it('generates JSON report', function (): void {
            $service = new SecurityAuditService();
            $report = $service->runFullAudit();
            $json = $service->generateReport($report, 'json');

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded)->toHaveKey('overall_score');
            expect($decoded)->toHaveKey('grade');
            expect($decoded)->toHaveKey('checks');
        });
    });
});

describe('SecurityAuditReport', function (): void {
    it('calculates grade from score', function (): void {
        expect(App\Domain\Security\ValueObjects\SecurityAuditReport::gradeFromScore(95))->toBe('A');
        expect(App\Domain\Security\ValueObjects\SecurityAuditReport::gradeFromScore(85))->toBe('B');
        expect(App\Domain\Security\ValueObjects\SecurityAuditReport::gradeFromScore(75))->toBe('C');
        expect(App\Domain\Security\ValueObjects\SecurityAuditReport::gradeFromScore(65))->toBe('D');
        expect(App\Domain\Security\ValueObjects\SecurityAuditReport::gradeFromScore(50))->toBe('F');
    });

    it('reports isPassing correctly', function (): void {
        $report = new App\Domain\Security\ValueObjects\SecurityAuditReport(
            checks: [],
            overallScore: 80,
            grade: 'B',
            timestamp: new DateTimeImmutable(),
        );

        expect($report->isPassing(70))->toBeTrue();
        expect($report->isPassing(90))->toBeFalse();
    });

    it('serializes to array', function (): void {
        $report = new App\Domain\Security\ValueObjects\SecurityAuditReport(
            checks: [
                new SecurityCheckResult(
                    name: 'test',
                    category: 'Test',
                    passed: true,
                    score: 100,
                ),
            ],
            overallScore: 100,
            grade: 'A',
            timestamp: new DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $array = $report->toArray();
        expect($array['overall_score'])->toBe(100);
        expect($array['grade'])->toBe('A');
        expect($array['checks'])->toHaveCount(1);
    });
});
