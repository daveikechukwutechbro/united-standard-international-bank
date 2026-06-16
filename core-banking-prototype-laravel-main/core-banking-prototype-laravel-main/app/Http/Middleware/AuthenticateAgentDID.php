<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\AgentProtocol\Services\AgentAuthenticationService;
use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for authenticating AI agents using multiple methods:
 * - DID signature verification (X-Agent-DID, X-Agent-Signature, X-Agent-Challenge headers)
 * - API key authentication (Authorization: AgentKey {key})
 * - Session token authentication (X-Agent-Session header)
 */
class AuthenticateAgentDID
{
    public function __construct(
        private readonly AgentAuthenticationService $authService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $requiredScope  Optional scope requirement
     */
    public function handle(Request $request, Closure $next, ?string $requiredScope = null): Response
    {
        $result = $this->authenticateAgent($request);

        if (! $result['success']) {
            return $this->unauthorizedResponse($result['error'] ?? 'Authentication failed');
        }

        /** @var Agent $agent */
        $agent = $result['agent'];

        // Check if agent is active
        if ($agent->status !== 'active') {
            return $this->forbiddenResponse('Agent is not active');
        }

        // Check scope if required
        if ($requiredScope !== null && isset($result['scopes'])) {
            if (! $this->hasRequiredScope($result['scopes'], $requiredScope)) {
                return $this->forbiddenResponse("Missing required scope: {$requiredScope}");
            }
        }

        // Attach agent and authentication info to request
        $request->merge([
            'authenticated_agent' => $agent,
            'agent_auth_method'   => $result['method'],
        ]);

        // Set the agent resolver for the request
        $request->attributes->set('agent', $agent);
        $request->attributes->set('agent_did', $agent->did);

        Log::debug('Agent authenticated', [
            'agent_id' => $agent->agent_id,
            'did'      => $agent->did,
            'method'   => $result['method'],
            'ip'       => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Attempt to authenticate the agent using available methods.
     *
     * @return array{success: bool, agent: ?Agent, method: ?string, scopes: ?array, error: ?string}
     */
    private function authenticateAgent(Request $request): array
    {
        // Try session token authentication first (fastest)
        $sessionToken = $request->header('X-Agent-Session');
        if ($sessionToken !== null) {
            $result = $this->authService->validateSession($sessionToken);
            if ($result['valid'] && $result['agent'] !== null) {
                return [
                    'success' => true,
                    'agent'   => $result['agent'],
                    'method'  => 'session',
                    'scopes'  => null,
                    'error'   => null,
                ];
            }
        }

        // Try API key authentication
        $authHeader = $request->header('Authorization');
        if ($authHeader !== null && str_starts_with($authHeader, 'AgentKey ')) {
            $apiKey = substr($authHeader, 9); // Remove 'AgentKey ' prefix
            $result = $this->authService->authenticateWithApiKey($apiKey);

            if ($result['success'] && $result['agent'] !== null) {
                return [
                    'success' => true,
                    'agent'   => $result['agent'],
                    'method'  => 'api_key',
                    'scopes'  => $result['scopes'] ?? null,
                    'error'   => null,
                ];
            }

            // If API key provided but invalid, return error immediately
            return [
                'success' => false,
                'agent'   => null,
                'method'  => null,
                'scopes'  => null,
                'error'   => $result['error'] ?? 'Invalid API key',
            ];
        }

        // Try DID signature authentication
        $did = $request->header('X-Agent-DID');
        $signature = $request->header('X-Agent-Signature');
        $challenge = $request->header('X-Agent-Challenge');
        $nonce = $request->header('X-Agent-Nonce');

        if ($did !== null && $signature !== null && $challenge !== null) {
            $result = $this->authService->authenticateWithDID(
                $did,
                $signature,
                $challenge,
                $nonce
            );

            if ($result['success'] && $result['agent'] !== null) {
                return [
                    'success' => true,
                    'agent'   => $result['agent'],
                    'method'  => 'did_signature',
                    'scopes'  => null,
                    'error'   => null,
                ];
            }

            return [
                'success' => false,
                'agent'   => null,
                'method'  => null,
                'scopes'  => null,
                'error'   => $result['error'] ?? 'DID authentication failed',
            ];
        }

        // No authentication method provided
        return [
            'success' => false,
            'agent'   => null,
            'method'  => null,
            'scopes'  => null,
            'error'   => 'No authentication credentials provided. Use X-Agent-Session, AgentKey authorization, or DID signature.',
        ];
    }

    /**
     * Check if the provided scopes include the required scope.
     */
    private function hasRequiredScope(?array $scopes, string $requiredScope): bool
    {
        if ($scopes === null || empty($scopes)) {
            return true; // Empty scopes means all scopes allowed
        }

        // Check for wildcard scope
        if (in_array('*', $scopes, true)) {
            return true;
        }

        // Check for exact match
        if (in_array($requiredScope, $scopes, true)) {
            return true;
        }

        // Check for hierarchical scope (e.g., 'payments:*' matches 'payments:create')
        $scopeParts = explode(':', $requiredScope);
        if (count($scopeParts) > 1) {
            $wildcardScope = $scopeParts[0] . ':*';
            if (in_array($wildcardScope, $scopes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an unauthorized response.
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error'   => 'Unauthorized',
            'message' => $message,
            'code'    => 'AGENT_AUTH_FAILED',
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
            'code'    => 'AGENT_ACCESS_DENIED',
        ], 403);
    }
}
