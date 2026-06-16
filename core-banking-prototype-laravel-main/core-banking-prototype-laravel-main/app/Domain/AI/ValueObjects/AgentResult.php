<?php

declare(strict_types=1);

namespace App\Domain\AI\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents the result of a domain agent's execution.
 *
 * @implements Arrayable<string, mixed>
 */
final class AgentResult implements Arrayable
{
    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     * @param  array<string>  $toolsUsed
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $agentName,
        public readonly string $content,
        public readonly float $confidence,
        public readonly array $toolResults,
        public readonly array $toolsUsed,
        public readonly ?string $llmProvider = null,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     * @param  array<string>  $toolsUsed
     */
    public static function fromTemplate(
        string $agentName,
        string $content,
        array $toolResults,
        array $toolsUsed,
    ): self {
        return new self(
            agentName: $agentName,
            content: $content,
            confidence: 0.85,
            toolResults: $toolResults,
            toolsUsed: $toolsUsed,
            llmProvider: null,
            metadata: ['mode' => 'template'],
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     * @param  array<string>  $toolsUsed
     * @param  array<string, mixed>  $metadata
     */
    public static function fromLlm(
        string $agentName,
        string $content,
        array $toolResults,
        array $toolsUsed,
        string $provider,
        array $metadata = [],
    ): self {
        return new self(
            agentName: $agentName,
            content: $content,
            confidence: 0.90,
            toolResults: $toolResults,
            toolsUsed: $toolsUsed,
            llmProvider: $provider,
            metadata: array_merge(['mode' => 'llm'], $metadata),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agent_name'   => $this->agentName,
            'content'      => $this->content,
            'confidence'   => $this->confidence,
            'tool_results' => $this->toolResults,
            'tools_used'   => $this->toolsUsed,
            'llm_provider' => $this->llmProvider,
            'metadata'     => $this->metadata,
        ];
    }
}
