<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use Illuminate\Support\Facades\Route;

/**
 * A04: Insecure Design.
 *
 * Verifies rate limiting on authentication endpoints and sensitive routes.
 */
class RateLimitingCheck implements SecurityCheckInterface
{
    /** @var array<string> Endpoints that must have rate limiting */
    private const RATE_LIMITED_PATTERNS = [
        'login',
        'register',
        'password',
        'forgot',
        'reset',
        'verify',
        'two-factor',
        '2fa',
    ];

    public function getName(): string
    {
        return 'rate_limiting';
    }

    public function getCategory(): string
    {
        return 'A04: Insecure Design';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $unprotectedEndpoints = [];

        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            // Check if this is an auth-related endpoint
            $isAuthEndpoint = false;
            foreach (self::RATE_LIMITED_PATTERNS as $pattern) {
                if (str_contains($uri, $pattern)) {
                    $isAuthEndpoint = true;

                    break;
                }
            }

            if (! $isAuthEndpoint) {
                continue;
            }

            // Check for throttle/rate limiting middleware
            $hasRateLimiting = false;
            foreach ($middleware as $mw) {
                if (str_contains((string) $mw, 'throttle') || str_contains((string) $mw, 'rate')) {
                    $hasRateLimiting = true;

                    break;
                }
            }

            if (! $hasRateLimiting) {
                $unprotectedEndpoints[] = $uri;
            }
        }

        if (! empty($unprotectedEndpoints)) {
            $findings[] = 'Auth endpoints without rate limiting: ' . implode(', ', array_slice($unprotectedEndpoints, 0, 5));
            $recommendations[] = 'Add throttle middleware to all authentication endpoints';
        }

        // Check global API rate limiting
        $apiMiddleware = config('app.middleware_groups.api', []);
        $hasGlobalThrottle = false;
        if (is_array($apiMiddleware)) {
            foreach ($apiMiddleware as $mw) {
                if (is_string($mw) && str_contains($mw, 'throttle')) {
                    $hasGlobalThrottle = true;

                    break;
                }
            }
        }

        if (! $hasGlobalThrottle) {
            // Check RouteServiceProvider or bootstrap for API rate limiting
            $routeServiceProvider = app_path('Providers/RouteServiceProvider.php');
            $bootstrapApp = base_path('bootstrap/app.php');

            $hasConfiguredRateLimiting = false;
            foreach ([$routeServiceProvider, $bootstrapApp] as $file) {
                if (file_exists($file) && str_contains((string) file_get_contents($file), 'RateLimiter')) {
                    $hasConfiguredRateLimiting = true;

                    break;
                }
            }

            if (! $hasConfiguredRateLimiting) {
                $findings[] = 'No global API rate limiting configured';
                $recommendations[] = 'Configure RateLimiter in RouteServiceProvider or bootstrap/app.php';
            }
        }

        $score = empty($findings) ? 100 : max(0, 100 - (count($findings) * 25));

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
