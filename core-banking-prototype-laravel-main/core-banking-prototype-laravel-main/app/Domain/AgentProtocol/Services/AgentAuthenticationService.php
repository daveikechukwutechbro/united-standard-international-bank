<?php

declare(strict_types=1);

namespace App\Domain\AgentProtocol\Services;

use App\Models\Agent;
use App\Models\AgentApiKey;
use App\Models\AgentSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for agent authentication including DID-based auth, API keys, and sessions.
 *
 * Supports multiple authentication methods:
 * - DID signature verification
 * - API key authentication
 * - OAuth2 token binding
 * - Session management
 */
class AgentAuthenticationService
{
    private const CACHE_PREFIX = 'agent_auth:';

    private const SESSION_TTL_HOURS = 24;

    private const API_KEY_LENGTH = 64;

    private const NONCE_TTL_MINUTES = 5;

    public function __construct(
        private readonly DIDService $didService
    ) {
    }

    /**
     * Authenticate an agent using DID signature.
     *
     * @param string $did Agent's DID
     * @param string $signature Signature of the challenge
     * @param string $challenge Challenge string that was signed
     * @param string|null $nonce Optional nonce for replay protection
     * @return array{success: bool, agent: ?Agent, session_token: ?string, expires_at: ?string, error: ?string}
     */
    public function authenticateWithDID(
        string $did,
        string $signature,
        string $challenge,
        ?string $nonce = null
    ): array {
        try {
            // Validate nonce if provided (replay protection)
            if ($nonce !== null && ! $this->validateNonce($nonce)) {
                return $this->authFailure('Invalid or expired nonce');
            }

            // Resolve DID to get public key
            $didDocument = $this->didService->resolveDID($did);
            if ($didDocument === null) {
                return $this->authFailure('DID not found or unresolvable');
            }

            // Get agent record
            $agent = Agent::where('did', $did)->first();
            if ($agent === null) {
                return $this->authFailure('Agent not registered');
            }

            // Check agent status
            if ($agent->status !== 'active') {
                return $this->authFailure('Agent is not active');
            }

            // Verify signature using DIDService
            $isValid = $this->didService->verifyDIDSignature(
                $did,
                $signature,
                $challenge
            );

            if (! $isValid) {
                $this->logAuthAttempt($did, 'did_signature', false, 'Invalid signature');

                return $this->authFailure('Invalid signature');
            }

            // Mark nonce as used
            if ($nonce !== null) {
                $this->consumeNonce($nonce);
            }

            // Create session
            $session = $this->createSession($agent);

            $this->logAuthAttempt($did, 'did_signature', true);

            return [
                'success'       => true,
                'agent'         => $agent,
                'session_token' => $session['token'],
                'expires_at'    => $session['expires_at'],
                'error'         => null,
            ];
        } catch (Exception $e) {
            Log::error('DID authentication failed', [
                'did'   => $did,
                'error' => $e->getMessage(),
            ]);

            return $this->authFailure('Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Authenticate an agent using API key.
     *
     * @param string $apiKey The API key
     * @return array{success: bool, agent: ?Agent, session_token: ?string, expires_at: ?string, error: ?string}
     */
    public function authenticateWithApiKey(string $apiKey): array
    {
        try {
            // Hash the API key to find it
            $keyHash = hash('sha256', $apiKey);

            $agentApiKey = AgentApiKey::where('key_hash', $keyHash)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($agentApiKey === null) {
                return $this->authFailure('Invalid or expired API key');
            }

            /** @var Agent|null $agent */
            $agent = $agentApiKey->agent;
            if (! $agent instanceof Agent || $agent->status !== 'active') {
                return $this->authFailure('Agent not found or inactive');
            }

            // Update last used timestamp
            $agentApiKey->update(['last_used_at' => now()]);

            // Create session
            $session = $this->createSession($agent);

            $this->logAuthAttempt($agent->did, 'api_key', true);

            return [
                'success'       => true,
                'agent'         => $agent,
                'session_token' => $session['token'],
                'expires_at'    => $session['expires_at'],
                'error'         => null,
            ];
        } catch (Exception $e) {
            Log::error('API key authentication failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->authFailure('Authentication failed');
        }
    }

    /**
     * Validate an existing session token.
     *
     * @param string $token Session token
     * @return array{valid: bool, agent: ?Agent, error: ?string}
     */
    public function validateSession(string $token): array
    {
        $cacheKey = self::CACHE_PREFIX . 'session:' . hash('sha256', $token);

        $sessionData = Cache::get($cacheKey);
        if ($sessionData === null) {
            // Check database for persistent sessions
            $session = AgentSession::where('token_hash', hash('sha256', $token))
                ->where('expires_at', '>', now())
                ->where('is_revoked', false)
                ->first();

            if ($session === null) {
                return ['valid' => false, 'agent' => null, 'error' => 'Invalid or expired session'];
            }

            /** @var Agent|null $agent */
            $agent = $session->agent;
            if (! $agent instanceof Agent) {
                return ['valid' => false, 'agent' => null, 'error' => 'Agent not found'];
            }

            // Re-cache the session
            Cache::put($cacheKey, [
                'agent_id'   => $agent->agent_id,
                'did'        => $agent->did,
                'expires_at' => $session->expires_at->toIso8601String(),
            ], $session->expires_at);

            return ['valid' => true, 'agent' => $agent, 'error' => null];
        }

        /** @var Agent|null $agent */
        $agent = Agent::where('agent_id', $sessionData['agent_id'])->first();

        return ['valid' => true, 'agent' => $agent, 'error' => null];
    }

    /**
     * Generate a new API key for an agent.
     *
     * @param Agent $agent The agent
     * @param string $name Key name/description
     * @param array<string> $scopes Allowed scopes
     * @param Carbon|null $expiresAt Expiration date
     * @return array{api_key: string, key_id: string, created_at: string}
     */
    public function generateApiKey(
        Agent $agent,
        string $name,
        array $scopes = [],
        ?Carbon $expiresAt = null
    ): array {
        $apiKey = Str::random(self::API_KEY_LENGTH);
        $keyId = 'ak_' . Str::random(16);

        AgentApiKey::create([
            'key_id'       => $keyId,
            'agent_id'     => $agent->agent_id,
            'name'         => $name,
            'key_hash'     => hash('sha256', $apiKey),
            'key_prefix'   => substr($apiKey, 0, 8),
            'scopes'       => $scopes,
            'is_active'    => true,
            'expires_at'   => $expiresAt,
            'last_used_at' => null,
        ]);

        Log::info('API key generated for agent', [
            'agent_id' => $agent->agent_id,
            'key_id'   => $keyId,
            'scopes'   => $scopes,
        ]);

        return [
            'api_key'    => $apiKey,
            'key_id'     => $keyId,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Revoke an API key.
     *
     * @param string $keyId Key ID to revoke
     * @param string $agentId Agent ID (for verification)
     * @return bool
     */
    public function revokeApiKey(string $keyId, string $agentId): bool
    {
        $key = AgentApiKey::where('key_id', $keyId)
            ->where('agent_id', $agentId)
            ->first();

        if ($key === null) {
            return false;
        }

        $key->update([
            'is_active'  => false,
            'revoked_at' => now(),
        ]);

        Log::info('API key revoked', [
            'agent_id' => $agentId,
            'key_id'   => $keyId,
        ]);

        return true;
    }

    /**
     * List API keys for an agent.
     *
     * @param string $agentId Agent ID
     * @return array<array{key_id: string, name: string, key_prefix: string, scopes: array, is_active: bool, last_used_at: ?string, expires_at: ?string}>
     */
    public function listApiKeys(string $agentId): array
    {
        return AgentApiKey::where('agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (AgentApiKey $key) => [
                'key_id'       => $key->key_id,
                'name'         => $key->name,
                'key_prefix'   => $key->key_prefix,
                'scopes'       => $key->scopes,
                'is_active'    => $key->is_active,
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'expires_at'   => $key->expires_at?->toIso8601String(),
                'created_at'   => $key->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Generate an authentication challenge/nonce.
     *
     * @param string $did Agent DID
     * @return array{challenge: string, nonce: string, expires_at: string}
     */
    public function generateChallenge(string $did): array
    {
        $nonce = Str::random(32);
        $challenge = base64_encode(json_encode([
            'did'       => $did,
            'nonce'     => $nonce,
            'timestamp' => now()->toIso8601String(),
            'action'    => 'authenticate',
        ]) ?: '');

        // Store nonce for validation
        Cache::put(
            self::CACHE_PREFIX . 'nonce:' . $nonce,
            ['did' => $did, 'created_at' => now()->toIso8601String()],
            now()->addMinutes(self::NONCE_TTL_MINUTES)
        );

        return [
            'challenge'  => $challenge,
            'nonce'      => $nonce,
            'expires_at' => now()->addMinutes(self::NONCE_TTL_MINUTES)->toIso8601String(),
        ];
    }

    /**
     * Revoke a session.
     *
     * @param string $token Session token
     * @return bool
     */
    public function revokeSession(string $token): bool
    {
        $tokenHash = hash('sha256', $token);

        // Remove from cache
        Cache::forget(self::CACHE_PREFIX . 'session:' . $tokenHash);

        // Mark as revoked in database
        $session = AgentSession::where('token_hash', $tokenHash)->first();
        if ($session !== null) {
            $session->update([
                'is_revoked' => true,
                'revoked_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Get active sessions for an agent.
     *
     * @param string $agentId Agent ID
     * @return array<array{session_id: string, created_at: string, expires_at: string, last_activity: ?string, ip_address: ?string}>
     */
    public function getActiveSessions(string $agentId): array
    {
        return AgentSession::where('agent_id', $agentId)
            ->where('expires_at', '>', now())
            ->where('is_revoked', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (AgentSession $session) => [
                'session_id'    => $session->session_id,
                'created_at'    => $session->created_at->toIso8601String(),
                'expires_at'    => $session->expires_at->toIso8601String(),
                'last_activity' => $session->last_activity_at?->toIso8601String(),
                'ip_address'    => $session->ip_address,
                'user_agent'    => $session->user_agent,
            ])
            ->toArray();
    }

    /**
     * Revoke all sessions for an agent.
     *
     * @param string $agentId Agent ID
     * @return int Number of sessions revoked
     */
    public function revokeAllSessions(string $agentId): int
    {
        $sessions = AgentSession::where('agent_id', $agentId)
            ->where('is_revoked', false)
            ->get();

        foreach ($sessions as $session) {
            Cache::forget(self::CACHE_PREFIX . 'session:' . $session->token_hash);
            $session->update([
                'is_revoked' => true,
                'revoked_at' => now(),
            ]);
        }

        return $sessions->count();
    }

    /**
     * Create a new session for an agent.
     */
    private function createSession(Agent $agent): array
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $sessionId = 'ses_' . Str::random(16);
        $expiresAt = now()->addHours(self::SESSION_TTL_HOURS);

        // Store in database
        AgentSession::create([
            'session_id'       => $sessionId,
            'agent_id'         => $agent->agent_id,
            'token_hash'       => $tokenHash,
            'expires_at'       => $expiresAt,
            'is_revoked'       => false,
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
            'last_activity_at' => now(),
        ]);

        // Cache for fast lookup
        Cache::put(
            self::CACHE_PREFIX . 'session:' . $tokenHash,
            [
                'agent_id'   => $agent->agent_id,
                'did'        => $agent->did,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            $expiresAt
        );

        return [
            'token'      => $token,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Validate a nonce hasn't been used.
     */
    private function validateNonce(string $nonce): bool
    {
        return Cache::has(self::CACHE_PREFIX . 'nonce:' . $nonce);
    }

    /**
     * Mark a nonce as consumed.
     */
    private function consumeNonce(string $nonce): void
    {
        Cache::forget(self::CACHE_PREFIX . 'nonce:' . $nonce);
    }

    /**
     * Log authentication attempt.
     */
    private function logAuthAttempt(string $did, string $method, bool $success, ?string $reason = null): void
    {
        Log::info('Agent authentication attempt', [
            'did'     => $did,
            'method'  => $method,
            'success' => $success,
            'reason'  => $reason,
            'ip'      => request()->ip(),
        ]);
    }

    /**
     * Create a failure response.
     *
     * @return array{success: false, agent: null, session_token: null, expires_at: null, error: string}
     */
    private function authFailure(string $error): array
    {
        return [
            'success'       => false,
            'agent'         => null,
            'session_token' => null,
            'expires_at'    => null,
            'error'         => $error,
        ];
    }
}
