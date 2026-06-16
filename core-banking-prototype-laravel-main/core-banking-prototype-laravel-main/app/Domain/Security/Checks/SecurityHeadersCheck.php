<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;

/**
 * A05: Security Misconfiguration.
 *
 * Verifies SecurityHeaders middleware is registered and key headers are configured.
 */
class SecurityHeadersCheck implements SecurityCheckInterface
{
    private const REQUIRED_HEADERS = [
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
        'Strict-Transport-Security',
        'Referrer-Policy',
    ];

    public function getName(): string
    {
        return 'security_headers';
    }

    public function getCategory(): string
    {
        return 'A05: Security Misconfiguration';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];

        // Check if SecurityHeaders middleware exists
        $middlewareFile = app_path('Http/Middleware/SecurityHeaders.php');
        if (! file_exists($middlewareFile)) {
            $findings[] = 'SecurityHeaders middleware not found';
            $recommendations[] = 'Create app/Http/Middleware/SecurityHeaders.php with OWASP-recommended headers';

            return new SecurityCheckResult(
                name: $this->getName(),
                category: $this->getCategory(),
                passed: false,
                score: 0,
                findings: $findings,
                recommendations: $recommendations,
                severity: 'high',
            );
        }

        $content = (string) file_get_contents($middlewareFile);
        $missingHeaders = [];

        foreach (self::REQUIRED_HEADERS as $header) {
            if (! str_contains($content, $header)) {
                $missingHeaders[] = $header;
            }
        }

        if (! empty($missingHeaders)) {
            $findings[] = 'Missing security headers: ' . implode(', ', $missingHeaders);
            $recommendations[] = 'Add missing headers to SecurityHeaders middleware';
        }

        // Check for Content-Security-Policy
        if (! str_contains($content, 'Content-Security-Policy')) {
            $findings[] = 'Content-Security-Policy header not configured';
            $recommendations[] = 'Add a strict Content-Security-Policy header';
        }

        $totalChecks = count(self::REQUIRED_HEADERS) + 1; // +1 for CSP
        $passed = $totalChecks - count($missingHeaders) - (str_contains($content, 'Content-Security-Policy') ? 0 : 1);
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
