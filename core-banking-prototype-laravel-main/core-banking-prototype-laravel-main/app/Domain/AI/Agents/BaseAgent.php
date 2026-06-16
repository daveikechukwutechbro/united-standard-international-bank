<?php

declare(strict_types=1);

namespace App\Domain\AI\Agents;

use App\Domain\AI\MCP\ToolRegistry;
use App\Domain\AI\Services\LLMOrchestrationService;
use App\Domain\AI\ValueObjects\AgentResult;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseAgent
{
    public function __construct(
        protected readonly ToolRegistry $toolRegistry,
        protected readonly LLMOrchestrationService $llmService,
    ) {
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    /**
     * @return array<string>
     */
    abstract public function getKeywords(): array;

    /**
     * @return array<string>
     */
    abstract public function getToolNames(): array;

    abstract protected function buildSystemPrompt(): string;

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     */
    abstract protected function composeTemplateResponse(string $query, array $toolResults): string;

    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(string $query, array $context, bool $useLlm): AgentResult
    {
        $startTime = microtime(true);
        $relevantTools = $this->selectRelevantTools($query);
        $toolResults = $this->executeTools($query, $relevantTools);
        $toolsUsed = array_keys($toolResults);

        if ($useLlm) {
            return $this->handleWithLlm($query, $toolResults, $toolsUsed, $startTime);
        }

        $content = $this->composeTemplateResponse($query, $toolResults);

        return AgentResult::fromTemplate(
            agentName: $this->getName(),
            content: $content,
            toolResults: $toolResults,
            toolsUsed: $toolsUsed,
        );
    }

    /**
     * @param  array<string>  $toolNames
     * @return array<string, array<string, mixed>>
     */
    protected function executeTools(string $query, array $toolNames): array
    {
        $results = [];

        foreach ($toolNames as $toolName) {
            if (! $this->toolRegistry->has($toolName)) {
                Log::warning("Agent {$this->getName()}: tool not found", ['tool' => $toolName]);

                continue;
            }

            try {
                $tool = $this->toolRegistry->get($toolName);
                $parameters = $this->buildToolParameters($toolName, $query);
                $result = $tool->execute($parameters);

                $results[$toolName] = $result->isSuccess()
                    ? $result->getData()
                    : ['error' => $result->getError()];
            } catch (Throwable $e) {
                Log::error("Agent {$this->getName()}: tool execution failed", [
                    'tool'  => $toolName,
                    'error' => $e->getMessage(),
                ]);
                $results[$toolName] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * @return array<string>
     */
    protected function selectRelevantTools(string $query): array
    {
        return $this->getToolNames();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildToolParameters(string $toolName, string $query): array
    {
        // Default: provide a demo account UUID for unauthenticated demo usage
        return match (true) {
            str_contains($toolName, 'account') => [
                'account_uuid' => '00000000-0000-0000-0000-000000000001',
            ],
            str_contains($toolName, 'transactions') => [
                'account_uuid' => '00000000-0000-0000-0000-000000000001',
                'query'        => $query,
            ],
            str_contains($toolName, 'compliance') => [
                'user_id' => 'demo-user',
            ],
            str_contains($toolName, 'exchange') => [
                'from_asset' => $this->extractAssetCode($query, 'BTC'),
                'to_asset'   => 'USD',
                'amount'     => 1.0,
            ],
            str_contains($toolName, 'payment') => [
                'query' => $query,
            ],
            default => ['query' => $query],
        };
    }

    protected function extractAssetCode(string $query, string $default = 'BTC'): string
    {
        $assets = ['BTC', 'ETH', 'USDC', 'USDT', 'GCU', 'EUR', 'GBP', 'USD'];
        $upper = strtoupper($query);

        foreach ($assets as $asset) {
            if (str_contains($upper, $asset)) {
                return $asset;
            }
        }

        return $default;
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     * @param  array<string>  $toolsUsed
     */
    private function handleWithLlm(
        string $query,
        array $toolResults,
        array $toolsUsed,
        float $startTime,
    ): AgentResult {
        $systemPrompt = $this->buildSystemPrompt();
        $contextBlock = $this->formatToolResultsForLlm($toolResults);
        $userMessage = "User query: {$query}\n\nTool results:\n{$contextBlock}";

        $llmResponse = $this->llmService->complete($systemPrompt, $userMessage);
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return AgentResult::fromLlm(
            agentName: $this->getName(),
            content: $llmResponse->getContent(),
            toolResults: $toolResults,
            toolsUsed: $toolsUsed,
            provider: $llmResponse->getProvider(),
            metadata: [
                'duration_ms'       => $durationMs,
                'prompt_tokens'     => $llmResponse->getPromptTokens(),
                'completion_tokens' => $llmResponse->getCompletionTokens(),
            ],
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     */
    private function formatToolResultsForLlm(array $toolResults): string
    {
        $lines = [];

        foreach ($toolResults as $tool => $data) {
            $lines[] = "[{$tool}]: " . json_encode($data, JSON_PRETTY_PRINT);
        }

        return implode("\n\n", $lines);
    }
}
