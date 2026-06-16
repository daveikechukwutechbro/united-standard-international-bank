<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$scopes
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        // If no user is authenticated, let other middleware handle it
        if (! $request->user()) {
            return $next($request);
        }

        // If user is authenticated via web session (no API token), allow access
        // Web sessions are protected by CSRF and session-based auth
        if (! $request->user()->currentAccessToken()) {
            return $next($request);
        }

        // Standard scope checking for API token-based authentication (security hardening)
        foreach ($scopes as $scope) {
            if ($request->user()->tokenCan($scope)) {
                return $next($request);
            }
        }

        // If none of the required scopes are present, deny access
        return response()->json([
            'message'         => 'Insufficient permissions. Required scope: ' . implode(' or ', $scopes),
            'error'           => 'INSUFFICIENT_SCOPE',
            'required_scopes' => $scopes,
        ], 403);
    }

    /**
     * Check if request requires write permissions based on HTTP method.
     *
     * @param  string  $method
     * @return bool
     */
    public static function requiresWriteScope(string $method): bool
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * Get the appropriate scope for an HTTP method.
     *
     * @param  string  $method
     * @return string
     */
    public static function getScopeForMethod(string $method): string
    {
        return self::requiresWriteScope($method) ? 'write' : 'read';
    }
}
