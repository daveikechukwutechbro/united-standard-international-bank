<?php

declare(strict_types=1);

namespace App\Domain\AgentProtocol\Services;

use App\Domain\AgentProtocol\Models\Agent;
use App\Domain\AI\MCP\ToolRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Bridge Service connecting Agent Protocol with MCP Tools.
 *
 * This service enables AI agents to:
 * - Execute MCP banking tools with proper DID-based authorization
 * - Have tools registered based on their capabilities and KYC level
 * - Maintain full audit trails of tool usage
 * - Respect rate limits and compliance requirements
 */
class AgentMCPBridgeService
{
    /**
     * Cache prefix for agent tool permissions.
     */
    private const CACHE_PREFIX = 'agent_mcp_bridge:';

    /**
     * Default cache TTL for tool permissions (1 hour).
     */
    private const PERMISSION_CACHE_TTL = 3600;

    /**
     * Tool capability mapping for agent KYC levels.
     *
     * @var array<string, array<string>>
     */
    private const KYC_TOOL_PERMISSIONS = [
        'unverified' => [
            'account.balance',
            'exchange.quote',
        ],
        'basic' => [
            'account.balance',
            'account.deposit',
            'exchange.quote',
            'compliance.kyc',
        ],
        'standard' => [
            'account.balance',
            'account.deposit',
            'account.withdraw',
            'exchange.quote',
            'exchange.trade',
            'payment.transfer',
            'payment.status',
            'compliance.kyc',
            'agent.reputation',
        ],
        'enhanced' => [
            'account.balance',
            'account.deposit',
            'account.withdraw',
            'account.create',
            'exchange.quote',
            'exchange.trade',
            'exchange.liquidity',
            'payment.transfer',
            'payment.status',
            'compliance.kyc',
            'compliance.aml',
            'agent.reputation',
            'agent.payment',
            'agent.escrow',
        ],
    ];

    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        /** @phpstan-ignore-next-line property.onlyWritten - Reserved for future agent registry operations */
        private readonly AgentRegistryService $agentRegistry,
        private readonly AgentKycIntegrationService $kycService,
    ) {
    }

    /**
     * Execute an MCP tool as a specific agent.
     *
     * @param string               $agentDid  The agent's DID
     * @param string               $toolName  The MCP tool name
     * @param array<string, mixed> $input     Tool input parameters
     *
     * @return array{success: bool, result: mixed, audit_id: string, execution_time_ms: float}
     *
     * @throws InvalidArgumentException If agent or tool not found
     * @throws RuntimeException         If agent lacks permission
     */
    public function executeToolAsAgent(
        string $agentDid,
        string $toolName,
        array $input = []
    ): array {
        $startTime = microtime(true);
        $auditId = Str::uuid()->toString();

        // Verify agent exists
        $agent = $this->getAgentByDid($agentDid);
        if ($agent === null) {
            throw new InvalidArgumentException("Agent not found: {$agentDid}");
        }

        // Verify tool exists
        if (! $this->toolRegistry->has($toolName)) {
            throw new InvalidArgumentException("Tool not found: {$toolName}");
        }

        // Check permissions
        if (! $this->canAgentUseTool($agentDid, $toolName)) {
            $this->auditToolExecution($auditId, $agentDid, $toolName, $input, null, 'permission_denied');
            throw new RuntimeException("Agent lacks permission to use tool: {$toolName}");
        }

        try {
            // Get and execute the tool
            $tool = $this->toolRegistry->get($toolName);

            // Add agent context to input
            $contextualInput = array_merge($input, [
                '_agent_did'  => $agentDid,
                '_agent_id'   => $agent->id,
                '_audit_id'   => $auditId,
                '_request_at' => now()->toIso8601String(),
            ]);

            // Execute the tool
            $result = $tool->execute($contextualInput);

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Audit successful execution
            $this->auditToolExecution($auditId, $agentDid, $toolName, $input, $result, 'success');

            Log::info('Agent tool execution completed', [
                'audit_id'          => $auditId,
                'agent_did'         => $agentDid,
                'tool'              => $toolName,
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success'           => true,
                'result'            => $result,
                'audit_id'          => $auditId,
                'execution_time_ms' => $executionTime,
            ];
        } catch (Throwable $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            // Audit failed execution
            $this->auditToolExecution($auditId, $agentDid, $toolName, $input, null, 'error', $e->getMessage());

            Log::error('Agent tool execution failed', [
                'audit_id'          => $auditId,
                'agent_did'         => $agentDid,
                'tool'              => $toolName,
                'error'             => $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success'           => false,
                'result'            => ['error' => $e->getMessage()],
                'audit_id'          => $auditId,
                'execution_time_ms' => $executionTime,
            ];
        }
    }

    /**
     * Register available tools for an agent based on capabilities and KYC level.
     *
     * @param string $agentDid The agent's DID
     *
     * @return array{registered_tools: array<string>, kyc_level: string, permissions_cached: bool}
     */
    public function registerAgentTools(string $agentDid): array
    {
        $agent = $this->getAgentByDid($agentDid);
        if ($agent === null) {
            throw new InvalidArgumentException("Agent not found: {$agentDid}");
        }

        // Get agent's KYC level
        $kycLevel = $this->getAgentKycLevel($agentDid);

        // Get permitted tools based on KYC level
        $permittedTools = self::KYC_TOOL_PERMISSIONS[$kycLevel] ?? self::KYC_TOOL_PERMISSIONS['unverified'];

        // Filter based on agent capabilities
        $agentCapabilities = $agent->capabilities ?? [];
        $registeredTools = $this->filterToolsByCapabilities($permittedTools, $agentCapabilities);

        // Cache the permissions
        $cacheKey = self::CACHE_PREFIX . 'permissions:' . Str::slug($agentDid);
        Cache::put($cacheKey, $registeredTools, self::PERMISSION_CACHE_TTL);

        Log::info('Agent tools registered', [
            'agent_did'        => $agentDid,
            'kyc_level'        => $kycLevel,
            'registered_count' => count($registeredTools),
        ]);

        return [
            'registered_tools'   => $registeredTools,
            'kyc_level'          => $kycLevel,
            'permissions_cached' => true,
        ];
    }

    /**
     * Get audit log for agent tool usage.
     *
     * @param string      $agentDid  The agent's DID
     * @param int         $limit     Maximum entries to return
     * @param string|null $status    Filter by status
     *
     * @return array<int, array<string, mixed>>
     */
    public function auditAgentToolUsage(
        string $agentDid,
        int $limit = 100,
        ?string $status = null
    ): array {
        $query = DB::table('agent_mcp_audit_logs')
            ->where('agent_did', $agentDid)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn ($row) => [
            'audit_id'          => $row->audit_id,
            'tool_name'         => $row->tool_name,
            'status'            => $row->status,
            'error_message'     => $row->error_message,
            'execution_time_ms' => $row->execution_time_ms,
            'created_at'        => $row->created_at,
        ])->values()->all();
    }

    /**
     * Check if an agent can use a specific tool.
     *
     * @param string $agentDid The agent's DID
     * @param string $toolName The tool name
     */
    public function canAgentUseTool(string $agentDid, string $toolName): bool
    {
        // Check cache first
        $cacheKey = self::CACHE_PREFIX . 'permissions:' . Str::slug($agentDid);
        $cachedPermissions = Cache::get($cacheKey);

        if ($cachedPermissions === null) {
            // Register tools if not cached
            $result = $this->registerAgentTools($agentDid);
            $cachedPermissions = $result['registered_tools'];
        }

        return in_array($toolName, $cachedPermissions, true);
    }

    /**
     * Get all tools available to an agent.
     *
     * @param string $agentDid The agent's DID
     *
     * @return array<string, array{name: string, description: string, category: string}>
     */
    public function getAvailableTools(string $agentDid): array
    {
        // Ensure permissions are registered
        $cacheKey = self::CACHE_PREFIX . 'permissions:' . Str::slug($agentDid);
        $cachedPermissions = Cache::get($cacheKey);

        if ($cachedPermissions === null) {
            $result = $this->registerAgentTools($agentDid);
            $cachedPermissions = $result['registered_tools'];
        }

        $availableTools = [];

        foreach ($cachedPermissions as $toolName) {
            if ($this->toolRegistry->has($toolName)) {
                $tool = $this->toolRegistry->get($toolName);
                $availableTools[$toolName] = [
                    'name'         => $tool->getName(),
                    'description'  => $tool->getDescription(),
                    'category'     => $tool->getCategory(),
                    'input_schema' => $tool->getInputSchema(),
                ];
            }
        }

        return $availableTools;
    }

    /**
     * Revoke an agent's tool permissions.
     *
     * @param string $agentDid The agent's DID
     * @param string $reason   Reason for revocation
     */
    public function revokeAgentPermissions(string $agentDid, string $reason): void
    {
        $cacheKey = self::CACHE_PREFIX . 'permissions:' . Str::slug($agentDid);
        Cache::forget($cacheKey);

        // Log the revocation
        $this->auditToolExecution(
            Str::uuid()->toString(),
            $agentDid,
            'permission_revocation',
            ['reason' => $reason],
            null,
            'revoked'
        );

        Log::warning('Agent tool permissions revoked', [
            'agent_did' => $agentDid,
            'reason'    => $reason,
        ]);
    }

    /**
     * Get tool usage statistics for an agent.
     *
     * @param string $agentDid The agent's DID
     *
     * @return array{total_executions: int, success_rate: float, most_used_tools: array<string, int>, avg_execution_time_ms: float}
     */
    public function getAgentToolStatistics(string $agentDid): array
    {
        $stats = DB::table('agent_mcp_audit_logs')
            ->where('agent_did', $agentDid)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful', ['success'])
            ->selectRaw('AVG(execution_time_ms) as avg_time')
            ->first();

        $mostUsed = DB::table('agent_mcp_audit_logs')
            ->where('agent_did', $agentDid)
            ->where('status', 'success')
            ->groupBy('tool_name')
            ->selectRaw('tool_name, COUNT(*) as count')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'tool_name')
            ->toArray();

        $total = (int) ($stats->total ?? 0);
        $successful = (int) ($stats->successful ?? 0);

        return [
            'total_executions'      => $total,
            'success_rate'          => $total > 0 ? round($successful / $total * 100, 2) : 0.0,
            'most_used_tools'       => $mostUsed,
            'avg_execution_time_ms' => round((float) ($stats->avg_time ?? 0), 2),
        ];
    }

    /**
     * Get agent by DID.
     */
    private function getAgentByDid(string $agentDid): ?Agent
    {
        return Agent::where('did', $agentDid)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get agent's KYC level.
     */
    private function getAgentKycLevel(string $agentDid): string
    {
        try {
            $kycStatus = $this->kycService->getAgentKycStatus($agentDid);

            return $kycStatus['level'] ?? 'unverified';
        } catch (Throwable) {
            return 'unverified';
        }
    }

    /**
     * Filter tools based on agent capabilities.
     *
     * @param array<string> $permittedTools     Tools permitted by KYC level
     * @param array<string> $agentCapabilities  Agent's declared capabilities
     *
     * @return array<string>
     */
    private function filterToolsByCapabilities(array $permittedTools, array $agentCapabilities): array
    {
        // Map capabilities to tool categories
        $capabilityToolMap = [
            'payments'        => ['account.', 'payment.'],
            'trading'         => ['exchange.'],
            'compliance'      => ['compliance.'],
            'escrow'          => ['agent.escrow'],
            'reputation'      => ['agent.reputation'],
            'ai_conversation' => ['agent.'],
        ];

        // If no capabilities specified, allow all permitted tools
        if (empty($agentCapabilities)) {
            return $permittedTools;
        }

        $allowedPrefixes = [];
        foreach ($agentCapabilities as $capability) {
            if (isset($capabilityToolMap[$capability])) {
                $allowedPrefixes = array_merge($allowedPrefixes, $capabilityToolMap[$capability]);
            }
        }

        // Always allow basic account and exchange quote
        $allowedPrefixes[] = 'account.balance';
        $allowedPrefixes[] = 'exchange.quote';

        return array_filter($permittedTools, function ($tool) use ($allowedPrefixes) {
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($tool, $prefix) || $tool === $prefix) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Audit tool execution.
     *
     * @param string               $auditId   Unique audit identifier
     * @param string               $agentDid  Agent's DID
     * @param string               $toolName  Tool name
     * @param array<string, mixed> $input     Tool input (sanitized)
     * @param mixed                $result    Tool result
     * @param string               $status    Execution status
     * @param string|null          $error     Error message if applicable
     */
    private function auditToolExecution(
        string $auditId,
        string $agentDid,
        string $toolName,
        array $input,
        mixed $result,
        string $status,
        ?string $error = null
    ): void {
        // Sanitize input to remove sensitive data
        $sanitizedInput = $this->sanitizeAuditData($input);

        DB::table('agent_mcp_audit_logs')->insert([
            'audit_id'          => $auditId,
            'agent_did'         => $agentDid,
            'tool_name'         => $toolName,
            'input_data'        => json_encode($sanitizedInput),
            'result_summary'    => is_array($result) ? json_encode(['keys' => array_keys($result)]) : null,
            'status'            => $status,
            'error_message'     => $error,
            'execution_time_ms' => null, // Set by caller if needed
            'created_at'        => now(),
        ]);
    }

    /**
     * Sanitize audit data to remove sensitive information.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitizeAuditData(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'key', 'credential', 'pin'];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_')) {
                // Skip internal context keys
                continue;
            }

            $lowerKey = strtolower($key);
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeAuditData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
