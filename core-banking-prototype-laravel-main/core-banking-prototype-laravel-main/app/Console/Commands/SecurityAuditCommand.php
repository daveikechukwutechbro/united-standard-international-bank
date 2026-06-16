<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Security\Services\SecurityAuditService;
use Illuminate\Console\Command;

class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit
        {--format=text : Output format (text, json, table)}
        {--check= : Run a specific check by name}
        {--min-score=70 : Minimum passing score (0-100)}
        {--ci : CI mode — exit with code 1 if audit fails}';

    protected $description = 'Run automated OWASP Top 10 security checks and generate an audit report';

    public function handle(): int
    {
        $service = new SecurityAuditService();
        $format = (string) $this->option('format');
        $minScore = (int) $this->option('min-score');
        $ciMode = (bool) $this->option('ci');
        $checkName = $this->option('check');

        // Run a single check if specified
        if (! empty($checkName)) {
            return $this->runSingleCheck($service, (string) $checkName, $format);
        }

        $this->info('Running FinAegis Security Audit...');
        $this->newLine();

        $report = $service->runFullAudit();

        if ($format === 'json') {
            $this->line($report->toJson());
        } elseif ($format === 'table') {
            $this->renderTable($report, $service);
        } else {
            $this->line($service->generateReport($report, 'text'));
        }

        // Summary
        $this->newLine();
        $passing = $report->isPassing($minScore);
        if ($passing) {
            $this->info("PASSED: Score {$report->overallScore}/100 (Grade: {$report->grade}) >= minimum {$minScore}");
        } else {
            $this->error("FAILED: Score {$report->overallScore}/100 (Grade: {$report->grade}) < minimum {$minScore}");
        }

        if ($ciMode && ! $passing) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runSingleCheck(SecurityAuditService $service, string $checkName, string $format): int
    {
        $available = $service->getAvailableChecks();

        if (! in_array($checkName, $available, true)) {
            $this->error("Unknown check: {$checkName}");
            $this->info('Available checks: ' . implode(', ', $available));

            return self::FAILURE;
        }

        $result = $service->runCheck($checkName);

        if ($result === null) {
            $this->error("Check '{$checkName}' returned no result");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->line((string) json_encode($result->toArray(), JSON_PRETTY_PRINT));
        } else {
            $status = $result->passed ? 'PASS' : 'FAIL';
            $this->info("[{$status}] {$result->name} ({$result->category})");
            $this->info("Score: {$result->score}/100 | Severity: {$result->severity}");

            if (! empty($result->findings)) {
                $this->newLine();
                $this->warn('Findings:');
                foreach ($result->findings as $finding) {
                    $this->line("  - {$finding}");
                }
            }

            if (! empty($result->recommendations)) {
                $this->newLine();
                $this->info('Recommendations:');
                foreach ($result->recommendations as $rec) {
                    $this->line("  > {$rec}");
                }
            }
        }

        return $result->passed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  \App\Domain\Security\ValueObjects\SecurityAuditReport  $report
     */
    private function renderTable($report, SecurityAuditService $service): void
    {
        $rows = [];
        foreach ($report->checks as $check) {
            $rows[] = [
                $check->passed ? 'PASS' : 'FAIL',
                $check->name,
                $check->category,
                "{$check->score}/100",
                $check->severity,
                count($check->findings),
            ];
        }

        $this->table(
            ['Status', 'Check', 'Category', 'Score', 'Severity', 'Findings'],
            $rows,
        );

        $this->newLine();
        $this->info("Overall: {$report->overallScore}/100 (Grade: {$report->grade})");
    }
}
