<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Compliance\Models\DataClassification;
use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

/**
 * Data classification completeness check.
 *
 * Verifies that sensitive data fields are classified and encryption
 * requirements are met per PCI DSS standards.
 */
class DataClassificationCheck implements SecurityCheckInterface
{
    public function getName(): string
    {
        return 'data_classification';
    }

    public function getCategory(): string
    {
        return 'PCI DSS';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $totalChecks = 4;
        $passed = 0;

        // 1. Check that classifications exist
        $totalClassifications = DataClassification::count();
        if ($totalClassifications > 0) {
            $passed++;
        } else {
            $findings[] = 'No data classifications have been defined';
            $recommendations[] = 'Run `pci:audit --section=classification` to seed default classifications';
        }

        // 2. Check that restricted/confidential fields have encryption required
        if ($totalClassifications > 0) {
            $sensitiveWithoutEncryption = DataClassification::whereIn('classification_level', ['restricted', 'confidential'])
                ->where('encryption_required', false)
                ->count();
            if ($sensitiveWithoutEncryption === 0) {
                $passed++;
            } else {
                $findings[] = "{$sensitiveWithoutEncryption} sensitive field(s) do not require encryption";
                $recommendations[] = 'Update data classifications to require encryption for restricted/confidential fields';
            }
        } else {
            $passed++; // Skip if no classifications exist (handled by check 1)
        }

        // 3. Check encryption verification status
        $requiresEncryption = DataClassification::where('encryption_required', true)->count();
        $encryptionVerified = DataClassification::where('encryption_required', true)
            ->where('encryption_verified', true)
            ->count();
        if ($requiresEncryption === 0 || $encryptionVerified >= $requiresEncryption) {
            $passed++;
        } else {
            $unverified = $requiresEncryption - $encryptionVerified;
            $findings[] = "{$unverified} field(s) require encryption but are not verified";
            $recommendations[] = 'Run encryption verification to validate encrypted field implementations';
        }

        // 4. Check that access logging is enabled for sensitive levels
        if ($totalClassifications > 0) {
            $sensitiveWithoutLogging = DataClassification::whereIn('classification_level', ['restricted', 'confidential', 'internal'])
                ->where('access_logging_enabled', false)
                ->count();
            if ($sensitiveWithoutLogging === 0) {
                $passed++;
            } else {
                $findings[] = "{$sensitiveWithoutLogging} field(s) at internal+ level lack access logging";
                $recommendations[] = 'Enable access logging for internal, confidential, and restricted fields';
            }
        } else {
            $passed++;
        }

        $score = (int) round(($passed / $totalChecks) * 100);

        return new SecurityCheckResult(
            name: $this->getName(),
            category: $this->getCategory(),
            passed: empty($findings),
            score: $score,
            findings: $findings,
            recommendations: $recommendations,
            severity: empty($findings) ? 'info' : 'medium',
        );
    }
}
