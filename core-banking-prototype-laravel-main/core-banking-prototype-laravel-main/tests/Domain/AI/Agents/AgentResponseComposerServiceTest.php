<?php

declare(strict_types=1);

namespace Tests\Domain\AI\Agents;

use App\Domain\AI\Services\AgentResponseComposerService;
use App\Domain\AI\ValueObjects\AgentResult;
use PHPUnit\Framework\TestCase;

class AgentResponseComposerServiceTest extends TestCase
{
    private AgentResponseComposerService $composer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->composer = new AgentResponseComposerService();
    }

    public function test_compose_empty_results_returns_fallback(): void
    {
        $response = $this->composer->compose([], 'Hello');

        $this->assertArrayHasKey('content', $response);
        $this->assertEquals(0.3, $response['confidence']);
        $this->assertEmpty($response['agents_used']);
    }

    public function test_compose_single_agent_returns_directly(): void
    {
        $result = AgentResult::fromTemplate(
            'Financial Advisor',
            'Your balance is $12,456.78',
            ['account.balance' => ['balance' => 1245678]],
            ['account.balance']
        );

        $response = $this->composer->compose([$result], 'Check balance');

        $this->assertEquals('Your balance is $12,456.78', $response['content']);
        $this->assertEquals(0.85, $response['confidence']);
        $this->assertEquals(['Financial Advisor'], $response['agents_used']);
        $this->assertEquals(['account.balance'], $response['tools_used']);
    }

    public function test_compose_multiple_agents_combines_responses(): void
    {
        $results = [
            AgentResult::fromTemplate('Financial Advisor', 'Balance: $100', [], ['account.balance']),
            AgentResult::fromTemplate('Trading Specialist', 'Rate: 1.5', [], ['exchange.quote']),
        ];

        $response = $this->composer->compose($results, 'Balance and rate');

        $this->assertStringContainsString('Financial Advisor', $response['content']);
        $this->assertStringContainsString('Trading Specialist', $response['content']);
        $this->assertCount(2, $response['agents_used']);
        $this->assertCount(2, $response['tools_used']);
        $this->assertTrue($response['metadata']['multi_agent']);
    }
}
