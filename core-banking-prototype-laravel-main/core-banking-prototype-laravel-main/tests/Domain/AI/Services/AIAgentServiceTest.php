<?php

declare(strict_types=1);

namespace Tests\Domain\AI\Services;

use App\Domain\AI\Services\AgentOrchestratorService;
use App\Domain\AI\Services\AIAgentService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AIAgentServiceTest extends TestCase
{
    private AIAgentService $service;

    /** @var AgentOrchestratorService&MockInterface */
    private AgentOrchestratorService $orchestrator; // @phpstan-ignore property.phpDocType

    protected function setUp(): void
    {
        parent::setUp();

        $this->orchestrator = Mockery::mock(AgentOrchestratorService::class); // @phpstan-ignore assign.propertyType
        $this->service = new AIAgentService($this->orchestrator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_chat_returns_correct_structure(): void
    {
        $this->orchestrator->shouldReceive('process')
            ->once()
            ->with('Hello', [])
            ->andReturn([
                'message_id' => 'test-uuid',
                'content'    => 'Hello response',
                'confidence' => 0.85,
                'tools_used' => ['account.balance'],
            ]);

        $result = $this->service->chat('Hello', 'conv-123', 1);

        $this->assertArrayHasKey('message_id', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('tools_used', $result);
        $this->assertArrayHasKey('context', $result);
    }

    public function test_chat_delegates_to_orchestrator(): void
    {
        $this->orchestrator->shouldReceive('process')
            ->once()
            ->with('Check balance', ['account' => '123'])
            ->andReturn([
                'message_id' => 'uuid-1',
                'content'    => 'Your balance is $12,456.78',
                'confidence' => 0.90,
                'tools_used' => ['account.balance'],
            ]);

        $result = $this->service->chat(
            'Check balance',
            'conv-1',
            1,
            ['account' => '123']
        );

        $this->assertEquals('Your balance is $12,456.78', $result['content']);
        $this->assertEquals(0.90, $result['confidence']);
        $this->assertEquals(['account' => '123'], $result['context']);
    }

    public function test_chat_preserves_context(): void
    {
        $context = ['previous_intent' => 'balance_check'];

        $this->orchestrator->shouldReceive('process')
            ->once()
            ->andReturn([
                'message_id' => 'uuid-1',
                'content'    => 'response',
                'confidence' => 0.85,
                'tools_used' => [],
            ]);

        $result = $this->service->chat('test', 'conv-123', 1, $context);

        $this->assertEquals($context, $result['context']);
    }

    public function test_store_feedback_does_not_throw(): void
    {
        $this->service->storeFeedback('msg-123', 1, 5, 'Great response');

        $this->assertTrue(true);
    }

    public function test_store_feedback_accepts_null_feedback(): void
    {
        $this->service->storeFeedback('msg-123', 1, 3, null);

        $this->assertTrue(true);
    }
}
