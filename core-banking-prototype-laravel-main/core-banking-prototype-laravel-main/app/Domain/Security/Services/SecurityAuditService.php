<?php

declare(strict_types=1);

namespace App\Domain\Security\Services;

use App\Domain\Security\Checks\AuthenticationCheck;
use App\Domain\Security\Checks\DataClassificationCheck;
use App\Domain\Security\Checks\DependencyVulnerabilityCheck;
use App\Domain\Security\Checks\EncryptionCheck;
use App\Domain\Security\Checks\InputValidationCheck;
use App\Domain\Security\Checks\PciDssComplianceCheck;
use App\Domain\Security\Checks\RateLimitingCheck;
use App\Domain\Security\Checks\SecurityHeadersCheck;
use App\Domain\Security\Checks\SensitiveDataExposureCheck;
use App\Domain\Security\Checks\SqlInjectionCheck;
use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityAuditReport;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use DateTimeImmutable;

class SecurityAuditService
{
    /** @var array<string, SecurityCheckInterface> */
    private array $checks = [];

    public function __construct()
    {
        $this->registerDefaultChecks();
    }

    /**
     * Run all registered security checks and return an audit report.
     */
    public function runFullAudit(): SecurityAuditReport
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[] = $check->run();
        }

        return $this->buildReport($results);
    }

    /**
     * Run a specific check by name.
     */
    public function runCheck(string $name): ?SecurityCheckResult
    {
        $check = $this->checks[$name] ?? null;

        return $check?->run();
    }

    /**
     * Get names of all available checks.
     *
     * @return array<string>
     */
    public function getAvailableChecks(): array
    {
        return array_keys($this->checks);
    }

    /**
     * Register a custom security check.
     */
    public function registerCheck(SecurityCheckInterface $check): void
    {
        $this->checks[$check->getName()] = $check;
    }

    /**
     * Generate a formatted report string.
     */
    public function generateReport(SecurityAuditReport $report, string $format = 'text'): string
    {
        return match ($format) {
            'json'  => $report->toJson(),
            'text'  => $this->formatTextReport($report),
            default => $this->formatTextReport($report),
        };
    }

    /**
     * @param  array<SecurityCheckResult>  $results
     */
    private function buildReport(array $results): SecurityAuditReport
    {
        $totalScore = 0;
        $count = count($results);

        foreach ($results as $result) {
            $totalScore += $result->score;
        }

        $overallScore = $count > 0 ? (int) round($totalScore / $count) : 0;
        $grade = SecurityAuditReport::gradeFromScore($overallScore);

        return new SecurityAuditReport(
            checks: $results,
            overallScore: $overallScore,
            grade: $grade,
            timestamp: new DateTimeImmutable(),
        );
    }

    private function formatTextReport(SecurityAuditReport $report): string
    {
        $lines = [];
        $lines[] = '=== FinAegis Security Audit Report ===';
        $lines[] = "Date: {$report->timestamp->format('Y-m-d H:i:s')}";
        $lines[] = "Overall Score: {$report->overallScore}/100 (Grade: {$report->grade})";
        $lines[] = '';

        foreach ($report->checks as $check) {
            $status = $check->passed ? 'PASS' : 'FAIL';
            $lines[] = "[{$status}] {$check->name} ({$check->category}) - Score: {$check->score}/100";

            if (! empty($check->findings)) {
                foreach ($check->findings as $finding) {
                    $lines[] = "  - {$finding}";
                }
            }

            if (! empty($check->recommendations)) {
                foreach ($check->recommendations as $rec) {
                    $lines[] = "  > {$rec}";
                }
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function registerDefaultChecks(): void
    {
        $defaultChecks = [
            new DependencyVulnerabilityCheck(),
            new SecurityHeadersCheck(),
            new SqlInjectionCheck(),
            new AuthenticationCheck(),
            new EncryptionCheck(),
            new RateLimitingCheck(),
            new InputValidationCheck(),
            new SensitiveDataExposureCheck(),
            new PciDssComplianceCheck(),
            new DataClassificationCheck(),
        ];

        foreach ($defaultChecks as $check) {
            $this->checks[$check->getName()] = $check;
        }
    }
}
