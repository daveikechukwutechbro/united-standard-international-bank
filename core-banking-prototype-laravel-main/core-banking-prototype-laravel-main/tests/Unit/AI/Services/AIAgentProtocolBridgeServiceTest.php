<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Services;

use App\Domain\AgentProtocol\DataObjects\ReputationScore;
use App\Domain\AgentProtocol\Models\Agent;
use App\Domain\AgentProtocol\Services\AgentPaymentIntegrationService;
use App\Domain\AgentProtocol\Services\AgentRegistryService;
use App\Domain\AgentProtocol\Services\DIDService;
use App\Domain\AgentProtocol\Services\EscrowService;
use App\Domain\AgentProtocol\Services\ReputationService;
use App\Domain\AI\Services\AIAgentProtocolBridgeService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the AI-AgentProtocol Bridge Service.
 *
 * Tests the integration between AI Domain and Agent Protocol for
 * payments, reputation, and escrow functionality.
 *
 * This is a pure unit test that uses mocks and does not require database.
 */
class AIAgentProtocolBridgeServiceTest extends TestCase
{
    private AIAgentProtocolBridgeService $bridgeService;

    /** @var AgentRegistryService&MockInterface */
    private MockInterface $agentRegistryMock;

    /** @var DIDService&MockInterface */
    private MockInterface $didServiceMock;

    /** @var AgentPaymentIntegrationService&MockInterface */
    private MockInterface $paymentIntegrationMock;

    /** @var ReputationService&MockInterface */
    private MockInterface $reputationServiceMock;

    /** @var EscrowService&MockInterface */
    private MockInterface $escrowServiceMock;

    /**
     * Skip default account creation - this test uses mocks only.
     */
    protected function shouldCreateDefaultAccountsInSetup(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AgentRegistryService&MockInterface $agentRegistryMock */
        $agentRegistryMock = Mockery::mock(AgentRegistryService::class);
        $this->agentRegistryMock = $agentRegistryMock;

        /** @var DIDService&MockInterface $didServiceMock */
        $didServiceMock = Mockery::mock(DIDService::class);
        $this->didServiceMock = $didServiceMock;

        /** @var AgentPaymentIntegrationService&MockInterface $paymentIntegrationMock */
        $paymentIntegrationMock = Mockery::mock(AgentPaymentIntegrationService::class);
        $this->paymentIntegrationMock = $paymentIntegrationMock;

        /** @var ReputationService&MockInterface $reputationServiceMock */
        $reputationServiceMock = Mockery::mock(ReputationService::class);
        $this->reputationServiceMock = $reputationServiceMock;

        /** @var EscrowService&MockInterface $escrowServiceMock */
        $escrowServiceMock = Mockery::mock(EscrowService::class);
        $this->escrowServiceMock = $escrowServiceMock;

        $this->bridgeService = new AIAgentProtocolBridgeService(
            $this->agentRegistryMock,
            $this->didServiceMock,
            $this->paymentIntegrationMock,
            $this->reputationServiceMock,
            $this->escrowServiceMock
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_register_ai_agent_with_protocol(): void
    {
        // Arrange
        $aiAgentName = 'test-ai-agent';
        $expectedDid = 'did:agent:ai_agent:test-ai-agent';
        $mockAgent = new Agent();
        $mockAgent->id = 1;
        $mockAgent->agent_id = 'ai-1';
        $mockAgent->did = $expectedDid;
        $mockAgent->name = $aiAgentName;
        $mockAgent->wallet_address = 'wallet-123';

        $this->agentRegistryMock
            ->shouldReceive('getAgentByDID')
            ->andReturn(null);

        $this->didServiceMock
            ->shouldReceive('generateDID')
            ->with('agent')
            ->andReturn($expectedDid);

        $this->agentRegistryMock
            ->shouldReceive('registerAgent')
            ->once()
            ->andReturn($mockAgent);

        $this->reputationServiceMock
            ->shouldReceive('initializeAgentReputation')
            ->with($expectedDid)
            ->once();

        // Act
        $result = $this->bridgeService->registerAIAgent($aiAgentName);

        // Assert
        $this->assertArrayHasKey('did', $result);
        $this->assertArrayHasKey('agent_id', $result);
        $this->assertArrayHasKey('wallet_address', $result);
        $this->assertEquals($expectedDid, $result['did']);
        $this->assertEquals(1, $result['agent_id']); // Returns $agent->id (1), not agent_id field
    }

    public function test_returns_existing_registration_if_already_registered(): void
    {
        // Arrange
        $aiAgentName = 'existing-ai-agent';
        $existingDid = 'did:agent:ai_agent:existing-ai-agent';
        $agentData = [
            'agent_id'       => 'ai-5',
            'did'            => $existingDid,
            'name'           => $aiAgentName,
            'wallet_address' => 'wallet-456',
        ];

        // Cache the DID
        Cache::put('ai_agent_protocol_bridge:existing-ai-agent', $existingDid, now()->addHours(24));

        $this->agentRegistryMock
            ->shouldReceive('getAgentByDID')
            ->with($existingDid)
            ->andReturn($agentData);

        // Act
        $result = $this->bridgeService->registerAIAgent($aiAgentName);

        // Assert
        $this->assertEquals($existingDid, $result['did']);
        $this->assertEquals('ai-5', $result['agent_id']);
    }

    public function test_can_initiate_payment_between_ai_agents(): void
    {
        // Arrange
        $fromAgent = 'buyer-ai-agent';
        $toAgent = 'seller-ai-agent';
        $fromDid = 'did:agent:ai_agent:buyer-ai-agent';
        $toDid = 'did:agent:ai_agent:seller-ai-agent';

        // Cache both DIDs
        Cache::put('ai_agent_protocol_bridge:buyer-ai-agent', $fromDid, now()->addHours(24));
        Cache::put('ai_agent_protocol_bridge:seller-ai-agent', $toDid, now()->addHours(24));

        // Fee is calculated internally from config: 100 * 0.025 = 2.50
        $this->reputationServiceMock
            ->shouldReceive('updateReputationFromTransaction')
            ->once();

        // Act
        $result = $this->bridgeService->initiateAIAgentPayment(
            $fromAgent,
            $toAgent,
            100.00,
            'USD',
            'test_payment'
        );

        // Assert
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('fees', $result);
        $this->assertEquals('initiated', $result['status']);
        $this->assertEquals(2.50, $result['fees']); // 100 * 0.025 = 2.50 (config default)
        $this->assertEquals($fromDid, $result['from_did']);
        $this->assertEquals($toDid, $result['to_did']);
    }

    public function test_can_create_escrow_for_ai_agents(): void
    {
        // Arrange
        $buyerAgent = 'escrow-buyer';
        $sellerAgent = 'escrow-seller';
        $buyerDid = 'did:agent:ai_agent:escrow-buyer';
        $sellerDid = 'did:agent:ai_agent:escrow-seller';

        Cache::put('ai_agent_protocol_bridge:escrow-buyer', $buyerDid, now()->addHours(24));
        Cache::put('ai_agent_protocol_bridge:escrow-seller', $sellerDid, now()->addHours(24));

        // Act
        $result = $this->bridgeService->createAIAgentEscrow(
            $buyerAgent,
            $sellerAgent,
            500.00,
            ['delivery_confirmed', 'quality_check_passed'],
            172800 // 48 hours
        );

        // Assert
        $this->assertArrayHasKey('escrow_id', $result);
        $this->assertArrayHasKey('buyer_did', $result);
        $this->assertArrayHasKey('seller_did', $result);
        $this->assertArrayHasKey('conditions', $result);
        $this->assertEquals($buyerDid, $result['buyer_did']);
        $this->assertEquals($sellerDid, $result['seller_did']);
        $this->assertEquals(500.00, $result['amount']);
        $this->assertCount(2, $result['conditions']);
    }

    public function test_can_get_ai_agent_reputation(): void
    {
        // Arrange
        $aiAgentName = 'reputed-agent';
        $did = 'did:agent:ai_agent:reputed-agent';

        Cache::put('ai_agent_protocol_bridge:reputed-agent', $did, now()->addHours(24));

        $reputationScore = new ReputationScore(
            agentId: $did,
            score: 75.0,
            trustLevel: 'high',
            totalTransactions: 42,
            successfulTransactions: 40,
            failedTransactions: 1,
            disputedTransactions: 1,
            successRate: 95.0,
        );

        $this->reputationServiceMock
            ->shouldReceive('getAgentReputation')
            ->with($did)
            ->andReturn($reputationScore);

        // Act
        $result = $this->bridgeService->getAIAgentReputation($aiAgentName);

        // Assert
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('transaction_count', $result);
        $this->assertEquals(75.0, $result['score']);
        $this->assertEquals('unranked', $result['level']); // 75 < 200 = 'unranked' in determineReputationLevel thresholds
        $this->assertEquals(42, $result['transaction_count']);
    }

    public function test_returns_unregistered_for_unknown_agent_reputation(): void
    {
        // Arrange - no cache entry, no database record

        // Act
        $result = $this->bridgeService->getAIAgentReputation('unknown-agent');

        // Assert
        $this->assertEquals(0.0, $result['score']);
        $this->assertEquals('unregistered', $result['level']);
    }

    public function test_can_check_reputation_threshold(): void
    {
        // Arrange
        $aiAgentName = 'threshold-agent';
        $did = 'did:agent:ai_agent:threshold-agent';

        Cache::put('ai_agent_protocol_bridge:threshold-agent', $did, now()->addHours(24));

        $this->reputationServiceMock
            ->shouldReceive('meetsThreshold')
            ->with($did, 'standard')
            ->andReturn(true);

        // Act
        $result = $this->bridgeService->meetsReputationThreshold($aiAgentName, 'standard');

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_payment_capabilities(): void
    {
        // Arrange
        $this->paymentIntegrationMock
            ->shouldReceive('getDefaultCurrency')
            ->andReturn('USD');

        // Act
        $result = $this->bridgeService->getPaymentCapabilities();

        // Assert
        $this->assertArrayHasKey('supported_currencies', $result);
        $this->assertArrayHasKey('fee_structure', $result);
        $this->assertArrayHasKey('limits', $result);
        $this->assertArrayHasKey('escrow_enabled', $result);
        $this->assertTrue($result['escrow_enabled']);
    }

    public function test_can_calculate_trust_between_ai_agents(): void
    {
        // Arrange
        $agent1 = 'trust-agent-1';
        $agent2 = 'trust-agent-2';
        $did1 = 'did:agent:ai_agent:trust-agent-1';
        $did2 = 'did:agent:ai_agent:trust-agent-2';

        Cache::put('ai_agent_protocol_bridge:trust-agent-1', $did1, now()->addHours(24));
        Cache::put('ai_agent_protocol_bridge:trust-agent-2', $did2, now()->addHours(24));

        // calculateTrustRelationship returns a float 0-100 scale
        $this->reputationServiceMock
            ->shouldReceive('calculateTrustRelationship')
            ->with($did1, $did2)
            ->andReturn(75.0);

        // Act
        $result = $this->bridgeService->calculateTrustBetweenAIAgents($agent1, $agent2);

        // Assert
        $this->assertArrayHasKey('trust_score', $result);
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertEquals(0.75, $result['trust_score']); // Normalized to 0-1
        $this->assertEquals('trusted', $result['recommendation']);
    }

    public function test_trust_recommendation_suggests_escrow_for_low_trust(): void
    {
        // Arrange
        $agent1 = 'new-agent-1';
        $agent2 = 'new-agent-2';
        $did1 = 'did:agent:ai_agent:new-agent-1';
        $did2 = 'did:agent:ai_agent:new-agent-2';

        Cache::put('ai_agent_protocol_bridge:new-agent-1', $did1, now()->addHours(24));
        Cache::put('ai_agent_protocol_bridge:new-agent-2', $did2, now()->addHours(24));

        // calculateTrustRelationship returns a float 0-100 scale
        $this->reputationServiceMock
            ->shouldReceive('calculateTrustRelationship')
            ->andReturn(10.0); // 10% trust = 0.1 normalized

        // Act
        $result = $this->bridgeService->calculateTrustBetweenAIAgents($agent1, $agent2);

        // Assert
        $this->assertEquals('untrusted_use_escrow', $result['recommendation']);
    }

    public function test_can_discover_ai_agents_by_capability(): void
    {
        // Arrange
        $this->agentRegistryMock
            ->shouldReceive('searchByCapability')
            ->with('ai_conversation')
            ->andReturn(collect([
                [
                    'did'          => 'did:agent:ai:1',
                    'name'         => 'agent-1',
                    'capabilities' => ['ai_conversation', 'automated_payments'],
                ],
                [
                    'did'          => 'did:agent:ai:2',
                    'name'         => 'agent-2',
                    'capabilities' => ['ai_conversation', 'escrow_transactions'],
                ],
            ]));

        // Act
        $result = $this->bridgeService->discoverAIAgents(['automated_payments']);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('agent-1', $result->first()['name']);
    }

    public function test_can_deactivate_ai_agent(): void
    {
        // Arrange
        $aiAgentName = 'deactivate-agent';
        $did = 'did:agent:ai_agent:deactivate-agent';

        Cache::put('ai_agent_protocol_bridge:deactivate-agent', $did, now()->addHours(24));

        $this->agentRegistryMock
            ->shouldReceive('updateAgentStatus')
            ->with($did, 'inactive')
            ->once();

        // Act
        $result = $this->bridgeService->deactivateAIAgent($aiAgentName);

        // Assert
        $this->assertTrue($result);
        $this->assertNull(Cache::get('ai_agent_protocol_bridge:deactivate-agent'));
    }

    public function test_deactivate_returns_false_for_unknown_agent(): void
    {
        // Act
        $result = $this->bridgeService->deactivateAIAgent('nonexistent-agent');

        // Assert
        $this->assertFalse($result);
    }
}
