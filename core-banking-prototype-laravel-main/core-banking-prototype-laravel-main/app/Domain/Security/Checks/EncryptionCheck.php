<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

/**
 * A02: Cryptographic Failures.
 *
 * Verifies APP_KEY, HTTPS config, bcrypt cost, and encryption settings.
 */
class EncryptionCheck implements SecurityCheckInterface
{
    public function getName(): string
    {
        return 'encryption';
    }

    public function getCategory(): string
    {
        return 'A02: Cryptographic Failures';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $totalChecks = 5;
        $passed = 0;

        // 1. Check APP_KEY is set and strong
        $appKey = config('app.key');
        if (empty($appKey)) {
            $findings[] = 'APP_KEY is not set';
            $recommendations[] = 'Run `php artisan key:generate` to set APP_KEY';
        } elseif (strlen((string) $appKey) < 32) {
            $findings[] = 'APP_KEY appears too short';
            $recommendations[] = 'Regenerate APP_KEY with `php artisan key:generate`';
        } else {
            $passed++;
        }

        // 2. Check encryption cipher
        $cipher = config('app.cipher', 'AES-256-CBC');
        if (! in_array($cipher, ['AES-256-CBC', 'AES-256-GCM'], true)) {
            $findings[] = "Weak encryption cipher: {$cipher}";
            $recommendations[] = 'Use AES-256-CBC or AES-256-GCM cipher';
        } else {
            $passed++;
        }

        // 3. Check HTTPS enforcement
        $appUrl = (string) config('app.url', '');
        $forceHttps = config('app.force_https', false);
        if (! str_starts_with($appUrl, 'https://') && ! $forceHttps) {
            $findings[] = 'APP_URL does not use HTTPS and force_https is not enabled';
            $recommendations[] = 'Set APP_URL to an https:// URL or enable force_https in config';
        } else {
            $passed++;
        }

        // 4. Check bcrypt rounds
        $bcryptRounds = (int) config('hashing.bcrypt.rounds', 12);
        if ($bcryptRounds < 10) {
            $findings[] = "Bcrypt rounds too low: {$bcryptRounds} (minimum recommended: 10)";
            $recommendations[] = 'Increase bcrypt rounds to at least 10 in config/hashing.php';
        } else {
            $passed++;
        }

        // 5. Check debug mode
        $debugMode = config('app.debug', false);
        if ($debugMode) {
            $findings[] = 'Application is running in debug mode';
            $recommendations[] = 'Set APP_DEBUG=false in production';
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
            severity: empty($findings) ? 'info' : 'high',
        );
    }
}
