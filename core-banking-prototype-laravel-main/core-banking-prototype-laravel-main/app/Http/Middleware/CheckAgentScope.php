<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\AgentProtocol\Enums\AgentScope;
use App\Models\Agent;
use App\Models\AgentApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for checking if an agent's API key has the required scopes.
 *
 * Uses AgentScope enum for type-safe scope validation with support for:
 * - Exact match: "payments:read"
 * - Category wildcard: "payments:*" (all payment scopes)
 * - Universal scope: "*" (all access)
 * - Empty scopes array means all allowed (backward compatibility)
 *
 * @see AgentScope for all available scopes and their descriptions
 */
class CheckAgentScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$scopes  Required scopes (comma-separated or multiple params)
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        // Get authenticated agent from request
        $agent = $request->attributes->get('agent');

        if (! $agent instanceof Agent) {
            return $this->unauthorizedResponse('Agent authentication required');
        }

        // Get the API key scopes from the authentication
        $apiKeyScopes = $this->getApiKeyScopes($request);

        // Handle comma-separated scopes in a single parameter
        $requiredScopes = [];
        foreach ($scopes as $scope) {
            foreach (explode(',', $scope) as $s) {
                $requiredScopes[] = trim($s);
            }
        }

        // Check if any required scope is missing using AgentScope enum
        $missingScopes = [];
        foreach ($requiredScopes as $required) {
            if (! AgentScope::hasScope($apiKeyScopes, $required)) {
                $missingScopes[] = $required;
            }
        }

        if (! empty($missingScopes)) {
            Log::warning('Agent API key missing required scopes', [
                'agent_id' => $agent->agent_id,
                'did'      => $agent->did,
                'required' => $requiredScopes,
                'missing'  => $missingScopes,
                'has'      => $apiKeyScopes,
            ]);

            return $this->forbiddenResponse(
                'Missing required scopes: ' . implode(', ', $missingScopes)
            );
        }

        return $next($request);
    }

    /**
     * Get API key scopes from the authenticated request.
     */
    private function getApiKeyScopes(Request $request): array
    {
        // Check if scopes were passed during authentication
        $authenticatedAgent = $request->get('authenticated_agent');
        if ($authenticatedAgent && isset($authenticatedAgent['scopes'])) {
            return $authenticatedAgent['scopes'];
        }

        // Try to get from API key if authenticated via API key
        $authMethod = $request->get('agent_auth_method');
        if ($authMethod === 'api_key') {
            // Look up the API key to get scopes
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'AgentKey ')) {
                $apiKey = substr($authHeader, 9);
                $keyHash = hash('sha256', $apiKey);

                $cacheKey = 'agent_key_scopes:' . $keyHash;
                $scopes = Cache::get($cacheKey);

                if ($scopes === null) {
                    $agentApiKey = AgentApiKey::where('key_hash', $keyHash)->first();
                    if ($agentApiKey) {
                        $scopes = $agentApiKey->scopes ?? [];
                        Cache::put($cacheKey, $scopes, 300); // Cache for 5 minutes
                    }
                }

                return $scopes ?? [];
            }
        }

        // For session or DID auth, use default scopes (or empty which means all allowed)
        if (in_array($authMethod, ['session', 'did_signature'])) {
            return config('agent_protocol.authentication.default_scopes', []);
        }

        return [];
    }

    /**
     * Return an unauthorized response.
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error'   => 'Unauthorized',
            'message' => $message,
            'code'    => 'AGENT_NOT_AUTHENTICATED',
        ], 401);
    }

    /**
     * Return a forbidden response.
     */
    private function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'error'   => 'Forbidden',
            'message' => $message,
            'code'    => 'SCOPE_REQUIRED',
        ], 403);
    }
}
