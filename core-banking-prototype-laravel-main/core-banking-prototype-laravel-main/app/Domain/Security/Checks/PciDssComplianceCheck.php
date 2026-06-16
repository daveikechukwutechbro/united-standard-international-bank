<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use Illuminate\Support\Facades\Config;

/**
 * PCI DSS compliance scorecard check.
 *
 * Validates encryption, key rotation policy, network segmentation,
 * and other PCI DSS requirements.
 */
class PciDssComplianceCheck implements SecurityCheckInterface
{
    public function getName(): string
    {
        return 'pci_dss_compliance';
    }

    public function getCategory(): string
    {
        return 'PCI DSS';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $totalChecks = 7;
        $passed = 0;

        // 1. Encryption algorithm compliance
        $cipher = Config::get('app.cipher', 'AES-256-CBC');
        $approvedCiphers = ['AES-128-CBC', 'AES-256-CBC', 'AES-128-GCM', 'AES-256-GCM'];
        if (in_array($cipher, $approvedCiphers, true)) {
            $passed++;
        } else {
            $findings[] = "Non-compliant encryption cipher: {$cipher}";
            $recommendations[] = 'Use an AES-128 or AES-256 cipher in CBC or GCM mode';
        }

        // 2. Key rotation policy configured
        $rotationInterval = (int) Config::get('compliance-certification.pci_dss.key_rotation.interval_days', 0);
        if ($rotationInterval > 0 && $rotationInterval <= 90) {
            $passed++;
        } else {
            $findings[] = $rotationInterval === 0
                ? 'Key rotation policy not configured'
                : "Key rotation interval ({$rotationInterval} days) exceeds 90-day maximum";
            $recommendations[] = 'Configure key rotation interval to 90 days or less';
        }

        // 3. Network segmentation enabled
        $segmentationEnabled = Config::get('compliance-certification.pci_dss.network_segmentation.enabled', false);
        if ($segmentationEnabled) {
            $passed++;
        } else {
            $findings[] = 'Network segmentation is not enabled';
            $recommendations[] = 'Enable PCI_NETWORK_SEGMENTATION_ENABLED for CDE isolation';
        }

        // 4. Strong APP_KEY
        $appKey = Config::get('app.key');
        if (! empty($appKey) && strlen((string) $appKey) >= 32) {
            $passed++;
        } else {
            $findings[] = 'Application encryption key is weak or missing';
            $recommendations[] = 'Generate a strong APP_KEY with `php artisan key:generate`';
        }

        // 5. HTTPS enforcement
        $appUrl = (string) Config::get('app.url', '');
        $forceHttps = Config::get('app.force_https', false);
        if (str_starts_with($appUrl, 'https://') || $forceHttps || Config::get('app.env') === 'local') {
            $passed++;
        } else {
            $findings[] = 'HTTPS is not enforced';
            $recommendations[] = 'Configure APP_URL with https:// or enable force_https';
        }

        // 6. Debug mode disabled (production)
        $debugMode = Config::get('app.debug', false);
        if (! $debugMode || Config::get('app.env') === 'local') {
            $passed++;
        } else {
            $findings[] = 'Debug mode is enabled in a non-local environment';
            $recommendations[] = 'Set APP_DEBUG=false in production';
        }

        // 7. Password hashing strength
        $bcryptRounds = (int) Config::get('hashing.bcrypt.rounds', 12);
        if ($bcryptRounds >= 10) {
            $passed++;
        } else {
            $findings[] = "Bcrypt cost factor ({$bcryptRounds}) is below minimum (10)";
            $recommendations[] = 'Increase bcrypt rounds to at least 10';
        }

        $score = (int) round(($passed / $totalChecks) * 100);

        return new SecurityCheckResult(
            name: $this->getName(),
            category: $this->getCategory(),
            passed: empty($findings),
            score: $score,
            findings: $findings,
            recommendations: $recommendations,
            severity: empty($findings) ? 'info' : ($score < 50 ? 'critical' : 'high'),
        );
    }
}
