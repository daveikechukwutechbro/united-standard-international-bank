<?php

declare(strict_types=1);

namespace Tests\Domain\AI\Agents;

use App\Domain\AI\Agents\ComplianceAgent;
use App\Domain\AI\Agents\FinancialAgent;
use App\Domain\AI\Agents\GeneralAgent;
use App\Domain\AI\Agents\TradingAgent;
use App\Domain\AI\Agents\TransferAgent;
use App\Domain\AI\MCP\ToolRegistry;
use App\Domain\AI\Services\AgentRouterService;
use App\Domain\AI\Services\LLMOrchestrationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AgentRouterServiceTest extends TestCase
{
    private AgentRouterService $router;

    protected function setUp(): void
    {
        parent::setUp();

        $toolRegistry = Mockery::mock(ToolRegistry::class);
        $llmService = Mockery::mock(LLMOrchestrationService::class);

        $this->router = new AgentRouterService();
        $this->router->registerAgent(new FinancialAgent($toolRegistry, $llmService)); // @phpstan-ignore argument.type, argument.type
        $this->router->registerAgent(new TradingAgent($toolRegistry, $llmService)); // @phpstan-ignore argument.type, argument.type
        $this->router->registerAgent(new ComplianceAgent($toolRegistry, $llmService)); // @phpstan-ignore argument.type, argument.type
        $this->router->registerAgent(new TransferAgent($toolRegistry, $llmService)); // @phpstan-ignore argument.type, argument.type
        $this->router->registerAgent(new GeneralAgent($toolRegistry, $llmService)); // @phpstan-ignore argument.type, argument.type
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_routes_balance_query_to_financial_agent(): void
    {
        $agents = $this->router->route('What is my account balance?');

        $this->assertNotEmpty($agents);
        $this->assertEquals('Financial Advisor', $agents[0]->getName());
    }

    public function test_routes_trade_query_to_trading_agent(): void
    {
        $agents = $this->router->route('Buy some BTC');

        $this->assertNotEmpty($agents);
        $this->assertEquals('Trading Specialist', $agents[0]->getName());
    }

    public function test_routes_kyc_query_to_compliance_agent(): void
    {
        $agents = $this->router->route('Check my KYC status');

        $this->assertNotEmpty($agents);
        $this->assertEquals('Compliance Officer', $agents[0]->getName());
    }

    public function test_routes_transfer_query_to_transfer_agent(): void
    {
        $agents = $this->router->route('Transfer payment to John');

        $this->assertNotEmpty($agents);
        $this->assertEquals('Transfer Agent', $agents[0]->getName());
    }

    public function test_routes_unknown_query_to_general_agent(): void
    {
        $agents = $this->router->route('xyzzy foobar baz');

        $this->assertNotEmpty($agents);
        $this->assertEquals('General Assistant', $agents[0]->getName());
    }

    public function test_caps_agents_at_three(): void
    {
        // A query that might match multiple agents
        $agents = $this->router->route('balance account transfer send payment exchange trade compliance kyc help');

        $this->assertLessThanOrEqual(3, count($agents));
    }

    public function test_get_agent_scores_returns_scores_for_all_agents(): void
    {
        $scores = $this->router->getAgentScores('Check my balance');

        $this->assertArrayHasKey('Financial Advisor', $scores);
        $this->assertArrayHasKey('Trading Specialist', $scores);
        $this->assertArrayHasKey('Compliance Officer', $scores);
        $this->assertArrayHasKey('Transfer Agent', $scores);
        $this->assertArrayHasKey('General Assistant', $scores);
    }

    public function test_financial_agent_scores_highest_for_balance_query(): void
    {
        $scores = $this->router->getAgentScores('What is my account balance?');

        $this->assertGreaterThan($scores['Trading Specialist'], $scores['Financial Advisor']);
        $this->assertGreaterThan($scores['Transfer Agent'], $scores['Financial Advisor']);
    }

    public function test_routes_exchange_rate_query_to_trading_agent(): void
    {
        $agents = $this->router->route('What is the GCU exchange rate?');

        $this->assertNotEmpty($agents);
        $firstAgent = $agents[0]->getName();
        $this->assertEquals('Trading Specialist', $firstAgent);
    }
}
