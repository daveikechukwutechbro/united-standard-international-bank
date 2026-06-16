<?php

declare(strict_types=1);

namespace Tests\Domain\AI\ValueObjects;

use App\Domain\AI\ValueObjects\AgentResult;
use PHPUnit\Framework\TestCase;

class AgentResultTest extends TestCase
{
    public function test_from_template_creates_result_with_template_mode(): void
    {
        $result = AgentResult::fromTemplate(
            'Financial Advisor',
            'Your balance is $100',
            ['account.balance' => ['balance' => 100]],
            ['account.balance']
        );

        $this->assertEquals('Financial Advisor', $result->agentName);
        $this->assertEquals('Your balance is $100', $result->content);
        $this->assertEquals(0.85, $result->confidence);
        $this->assertNull($result->llmProvider);
        $this->assertEquals('template', $result->metadata['mode']);
    }

    public function test_from_llm_creates_result_with_llm_mode(): void
    {
        $result = AgentResult::fromLlm(
            'Financial Advisor',
            'LLM generated response',
            [],
            ['account.balance'],
            'anthropic',
            ['tokens' => 100]
        );

        $this->assertEquals(0.90, $result->confidence);
        $this->assertEquals('anthropic', $result->llmProvider);
        $this->assertEquals('llm', $result->metadata['mode']);
        $this->assertEquals(100, $result->metadata['tokens']);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $result = AgentResult::fromTemplate('Agent', 'Content', [], ['tool1']);
        $array = $result->toArray();

        $this->assertArrayHasKey('agent_name', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('confidence', $array);
        $this->assertArrayHasKey('tool_results', $array);
        $this->assertArrayHasKey('tools_used', $array);
        $this->assertArrayHasKey('llm_provider', $array);
        $this->assertArrayHasKey('metadata', $array);
    }
}
