<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for checking if an agent has specific capabilities.
 *
 * Capabilities are features/operations an agent is registered to perform.
 * This is different from scopes (which are API access permissions).
 *
 * Common capabilities:
 * - payments: Can send/receive payments
 * - escrow: Can participate in escrow transactions
 * - marketplace: Can list/purchase marketplace items
 * - governance: Can participate in governance votes
 * - premium: Has premium tier features
 */
class CheckAgentCapability
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$capabilities  Required capabilities (comma-separated or multiple params)
     */
    public function handle(Request $request, Closure $next, string ...$capabilities): Response
    {
        // Get authenticated agent from request
        $agent = $request->attributes->get('agent');

        if (! $agent instanceof Agent) {
            return $this->unauthorizedResponse('Agent authentication required');
        }

        // Check if agent has all required capabilities
        /** @var array<string>|null $rawCapabilities */
        $rawCapabilities = $agent->capabilities;
        $agentCapabilities = is_array($rawCapabilities) ? $rawCapabilities : [];

        // Handle comma-separated capabilities in a single parameter
        $requiredCapabilities = [];
        foreach ($capabilities as $capability) {
            foreach (explode(',', $capability) as $cap) {
                $requiredCapabilities[] = trim($cap);
            }
        }

        $missingCapabilities = [];
        foreach ($requiredCapabilities as $required) {
            if (! $this->hasCapability($agentCapabilities, $required)) {
                $missingCapabilities[] = $required;
            }
        }

        if (! empty($missingCapabilities)) {
            Log::warning('Agent missing required capabilities', [
                'agent_id' => $agent->agent_id,
                'did'      => $agent->did,
                'required' => $requiredCapabilities,
                'missing'  => $missingCapabilities,
                'has'      => $agentCapabilities,
            ]);

            return $this->forbiddenResponse(
                'Missing required capabilities: ' . implode(', ', $missingCapabilities)
            );
        }

        return $next($request);
    }

    /**
     * Check if agent has a specific capability.
     *
     * Supports:
     * - Exact match: "payments"
     * - Wildcard: "*" (has all capabilities)
     * - Hierarchical: "payments:advanced" matches "payments:*"
     */
    private function hasCapability(array $agentCapabilities, string $required): bool
    {
        // Wildcard capability means agent can do everything
        if (in_array('*', $agentCapabilities, true)) {
            return true;
        }

        // Exact match
        if (in_array($required, $agentCapabilities, true)) {
            return true;
        }

        // Check for hierarchical capabilities (e.g., "payments:*" covers "payments:advanced")
        $requiredParts = explode(':', $required);
        if (count($requiredParts) > 1) {
            $wildcardCapability = $requiredParts[0] . ':*';
            if (in_array($wildcardCapability, $agentCapabilities, true)) {
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
            'code'    => 'CAPABILITY_REQUIRED',
        ], 403);
    }
}
