<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AgentOrchestratorService
{
    public function __construct(
        private readonly AgentRouterService $router,
        private readonly AgentResponseComposerService $composer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function process(string $query, array $context = []): array
    {
        $startTime = microtime(true);

        try {
            $agents = $this->router->route($query);
            $useLlm = $this->isLlmEnabled();

            $results = [];
            foreach ($agents as $agent) {
                $results[] = $agent->handle($query, $context, $useLlm);
            }

            $response = $this->composer->compose($results, $query);
            $response['message_id'] = Str::uuid()->toString();
            $response['response_time_ms'] = (int) ((microtime(true) - $startTime) * 1000);

            return $response;
        } catch (Throwable $e) {
            Log::error('AgentOrchestrator: processing failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'message_id'       => Str::uuid()->toString(),
                'content'          => 'I encountered an error processing your request. Please try again.',
                'confidence'       => 0.0,
                'tools_used'       => [],
                'agents_used'      => [],
                'metadata'         => ['error' => true],
                'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    private function isLlmEnabled(): bool
    {
        if (config('ai.demo_mode', true)) {
            return false;
        }

        // Check if any API key is configured
        return config('ai.providers.openai.api_key') || config('ai.providers.anthropic.api_key');
    }
}
