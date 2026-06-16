<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use Illuminate\Support\Facades\Route;

/**
 * A07: Identification and Authentication Failures.
 *
 * Verifies auth middleware on API routes and auth configuration.
 */
class AuthenticationCheck implements SecurityCheckInterface
{
    /** @var array<string> Route prefixes that should be protected */
    private const PROTECTED_PREFIXES = [
        'api/accounts',
        'api/wallets',
        'api/transfers',
        'api/admin',
        'api/lending',
        'api/treasury',
        'api/compliance',
    ];

    /** @var array<string> Routes that may legitimately be public */
    private const PUBLIC_ALLOWED = [
        'api/health',
        'api/status',
        'api/docs',
        'sanctum/csrf-cookie',
    ];

    public function getName(): string
    {
        return 'authentication';
    }

    public function getCategory(): string
    {
        return 'A07: Identification and Authentication Failures';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $unprotectedRoutes = [];

        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            // Skip non-API routes
            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            // Skip allowed public routes
            $isPublicAllowed = false;
            foreach (self::PUBLIC_ALLOWED as $publicPrefix) {
                if (str_starts_with($uri, $publicPrefix)) {
                    $isPublicAllowed = true;

                    break;
                }
            }
            if ($isPublicAllowed) {
                continue;
            }

            // Check if route should be protected
            foreach (self::PROTECTED_PREFIXES as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $hasAuth = false;
                    foreach ($middleware as $mw) {
                        if (str_contains((string) $mw, 'auth') || str_contains((string) $mw, 'sanctum')) {
                            $hasAuth = true;

                            break;
                        }
                    }

                    if (! $hasAuth) {
                        $unprotectedRoutes[] = $uri;
                    }

                    break;
                }
            }
        }

        if (! empty($unprotectedRoutes)) {
            $findings[] = 'API routes without auth middleware: ' . implode(', ', array_slice($unprotectedRoutes, 0, 10));
            if (count($unprotectedRoutes) > 10) {
                $findings[] = '... and ' . (count($unprotectedRoutes) - 10) . ' more unprotected routes';
            }
            $recommendations[] = 'Add auth:sanctum or auth middleware to all protected API routes';
        }

        // Check session configuration
        $sessionDriver = config('session.driver');
        if ($sessionDriver === 'file') {
            $findings[] = 'Session driver is set to "file" — not recommended for production';
            $recommendations[] = 'Use Redis or database session driver in production';
        }

        $totalProtected = count(self::PROTECTED_PREFIXES);
        $unprotectedCount = min(count($unprotectedRoutes), $totalProtected);
        $score = (int) round((($totalProtected - $unprotectedCount) / max(1, $totalProtected)) * 100);

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
