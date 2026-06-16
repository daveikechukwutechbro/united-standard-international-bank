<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\DataClassificationService;
use App\Domain\Compliance\Services\Certification\EncryptionVerificationService;
use App\Domain\Compliance\Services\Certification\KeyRotationService;
use App\Domain\Compliance\Services\Certification\NetworkSegmentationService;
use Illuminate\Console\Command;

class PciDssAuditCommand extends Command
{
    protected $signature = 'pci:audit
        {--section=all : Section to audit (all, classification, encryption, key-rotation, network)}
        {--format=text : Output format (text, json)}
        {--seed : Seed default data classifications}';

    protected $description = 'Run PCI DSS compliance audit checks';

    public function handle(
        DataClassificationService $classificationService,
        EncryptionVerificationService $encryptionService,
        KeyRotationService $keyRotationService,
        NetworkSegmentationService $networkService,
    ): int {
        $section = $this->option('section');
        $format = $this->option('format');
        $results = [];

        $this->info('PCI DSS Compliance Audit');
        $this->info('========================');
        $this->newLine();

        if ($this->option('seed')) {
            $this->info('Seeding default data classifications...');
            $seedResult = $classificationService->seedDefaultClassifications();
            $this->info("Created: {$seedResult['created']}, Updated: {$seedResult['updated']}");
            $this->newLine();

            $this->info('Initializing default key inventory...');
            $keyResult = $keyRotationService->initializeDefaultKeys();
            $this->info("Registered: {$keyResult['registered']} of {$keyResult['total']} keys");
            $this->newLine();
        }

        // Data Classification
        if (in_array($section, ['all', 'classification'], true)) {
            $this->info('[1/4] Data Classification');
            $classificationReport = $classificationService->generateComplianceReport();
            $results['classification'] = $classificationReport;

            if ($format === 'text') {
                $this->line("  Total classifications: {$classificationReport['total_classifications']}");
                foreach ($classificationReport['by_level'] as $level => $count) {
                    $this->line("    {$level}: {$count}");
                }
                $rate = $classificationReport['encryption']['compliance_rate'];
                $color = $rate >= 90 ? 'green' : ($rate >= 70 ? 'yellow' : 'red');
                $this->line("  Encryption compliance: <fg={$color}>{$rate}%</>");
            }
            $this->newLine();
        }

        // Encryption Verification
        if (in_array($section, ['all', 'encryption'], true)) {
            $this->info('[2/4] Encryption Verification');
            $encryptionReport = $encryptionService->runVerification();
            $results['encryption'] = $encryptionReport;

            if ($format === 'text') {
                $summary = $encryptionReport['summary'];
                $this->line("  Total checks: {$summary['total_checks']}");
                $this->line("  Passed: {$summary['passed']}");
                $this->line("  Failed: {$summary['failed']}");
                $score = $summary['score'];
                $color = $score >= 90 ? 'green' : ($score >= 70 ? 'yellow' : 'red');
                $this->line("  Score: <fg={$color}>{$score}%</>");
            }
            $this->newLine();
        }

        // Key Rotation
        if (in_array($section, ['all', 'key-rotation'], true)) {
            $this->info('[3/4] Key Rotation Status');
            $rotationReport = $keyRotationService->generateRotationReport();
            $results['key_rotation'] = $rotationReport;

            if ($format === 'text') {
                $this->line("  Total keys: {$rotationReport['total_keys']}");
                $this->line("  Active: {$rotationReport['active']}");
                $overdueColor = $rotationReport['overdue'] > 0 ? 'red' : 'green';
                $this->line("  Overdue: <fg={$overdueColor}>{$rotationReport['overdue']}</>");
                $this->line("  Due soon: {$rotationReport['due_soon']}");
                $rate = $rotationReport['compliance_rate'];
                $color = $rate >= 90 ? 'green' : ($rate >= 70 ? 'yellow' : 'red');
                $this->line("  Compliance rate: <fg={$color}>{$rate}%</>");
            }
            $this->newLine();
        }

        // Network Segmentation
        if (in_array($section, ['all', 'network'], true)) {
            $this->info('[4/4] Network Segmentation');
            $networkReport = $networkService->verifySegmentation();
            $results['network_segmentation'] = $networkReport;

            if ($format === 'text') {
                $this->line('  Segmentation enabled: ' . ($networkReport['segmentation_enabled'] ? 'Yes' : 'No'));
                $this->line("  Checks passed: {$networkReport['passed']}/{$networkReport['total']}");
                $score = $networkReport['score'];
                $color = $score >= 90 ? 'green' : ($score >= 70 ? 'yellow' : 'red');
                $this->line("  Score: <fg={$color}>{$score}%</>");
            }
            $this->newLine();
        }

        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->info('PCI DSS audit complete.');

        return Command::SUCCESS;
    }
}
