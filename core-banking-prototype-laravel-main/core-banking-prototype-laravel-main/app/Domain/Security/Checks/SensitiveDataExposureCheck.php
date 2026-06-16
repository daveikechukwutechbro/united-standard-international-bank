<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

/**
 * A02: Cryptographic Failures / Sensitive Data Exposure.
 *
 * Checks .env patterns, .gitignore, and sensitive file exposure.
 */
class SensitiveDataExposureCheck implements SecurityCheckInterface
{
    /** @var array<string> Files that should be in .gitignore */
    private const SENSITIVE_FILES = [
        '.env',
        '.env.local',
        '.env.production',
        'storage/oauth-private.key',
        'storage/oauth-public.key',
    ];

    /** @var array<string> Patterns that indicate hardcoded secrets */
    private const SECRET_PATTERNS = [
        '/(?:password|secret|token|key|api_key)\s*=\s*[\'"][^\'"]{8,}[\'"]/i',
    ];

    public function getName(): string
    {
        return 'sensitive_data_exposure';
    }

    public function getCategory(): string
    {
        return 'A02: Cryptographic Failures';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];

        // Check .gitignore exists and covers sensitive files
        $gitignorePath = base_path('.gitignore');
        if (! file_exists($gitignorePath)) {
            $findings[] = '.gitignore file not found';
            $recommendations[] = 'Create a .gitignore file to exclude sensitive files';
        } else {
            $gitignoreContent = (string) file_get_contents($gitignorePath);

            foreach (self::SENSITIVE_FILES as $sensitiveFile) {
                $baseName = basename($sensitiveFile);
                if (! str_contains($gitignoreContent, $baseName) && ! str_contains($gitignoreContent, $sensitiveFile)) {
                    $findings[] = "{$sensitiveFile} not in .gitignore";
                }
            }
        }

        // Check .env.example doesn't contain real secrets
        $envExamplePath = base_path('.env.example');
        if (file_exists($envExamplePath)) {
            $content = (string) file_get_contents($envExamplePath);

            foreach (self::SECRET_PATTERNS as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $findings[] = '.env.example may contain real secrets';
                    $recommendations[] = 'Replace real values in .env.example with placeholders';

                    break;
                }
            }
        }

        // Check for exposed storage files
        $publicStoragePath = public_path('storage');
        if (is_link($publicStoragePath) || is_dir($publicStoragePath)) {
            $logDir = storage_path('logs');
            $publicLogLink = $publicStoragePath . '/logs';
            if (is_dir($publicLogLink) || is_link($publicLogLink)) {
                $findings[] = 'Storage logs directory may be publicly accessible';
                $recommendations[] = 'Ensure storage/logs is not exposed via public/storage symlink';
            }
        }

        if (! empty($findings)) {
            $recommendations[] = 'Review all exposed files and ensure sensitive data is not committed to version control';
        }

        $totalChecks = count(self::SENSITIVE_FILES) + 2; // +1 for env.example, +1 for storage
        $issueCount = count($findings);
        $score = (int) round((max(0, $totalChecks - $issueCount) / $totalChecks) * 100);

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
