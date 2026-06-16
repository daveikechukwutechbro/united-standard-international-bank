<?php

declare(strict_types=1);

namespace Tests\Domain\Fraud\Services;

use App\Domain\Account\Models\Transaction;
use App\Domain\Fraud\Models\FraudScore;
use App\Domain\Fraud\Services\BehavioralAnalysisService;
use App\Domain\Fraud\Services\DeviceFingerprintService;
use App\Domain\Fraud\Services\FraudCaseService;
use App\Domain\Fraud\Services\FraudDetectionService;
use App\Domain\Fraud\Services\MachineLearningService;
use App\Domain\Fraud\Services\RuleEngineService;
use App\Models\User;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Traits\InvokesPrivateMethods;

/**
 * Unit tests for FraudDetectionService.
 *
 * These are pure unit tests that don't require database or Redis.
 */
class FraudDetectionServiceTest extends TestCase
{
    use InvokesPrivateMethods;

    private FraudDetectionService $service;

    private MockInterface $ruleEngine;

    private MockInterface $behavioralAnalysis;

    private MockInterface $deviceService;

    private MockInterface $mlService;

    private MockInterface $caseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ruleEngine = Mockery::mock(RuleEngineService::class);
        $this->behavioralAnalysis = Mockery::mock(BehavioralAnalysisService::class);
        $this->deviceService = Mockery::mock(DeviceFingerprintService::class);
        $this->mlService = Mockery::mock(MachineLearningService::class);
        $this->caseService = Mockery::mock(FraudCaseService::class);

        $this->service = new FraudDetectionService(
            $this->ruleEngine,
            $this->behavioralAnalysis,
            $this->deviceService,
            $this->mlService,
            $this->caseService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_total_score_with_all_components(): void
    {
        $ruleResults = ['total_score' => 50, 'triggered_rules' => ['rule1']];
        $behavioralResults = ['risk_score' => 40];
        $deviceResults = ['risk_score' => 30];
        $mlResults = ['score' => 60];

        $score = $this->invokeMethod(
            $this->service,
            'calculateTotalScore',
            [$ruleResults, $behavioralResults, $deviceResults, $mlResults]
        );

        // Expected: (50 * 0.35) + (40 * 0.25) + (30 * 0.20) + (60 * 0.20)
        // = 17.5 + 10 + 6 + 12 = 45.5
        $this->assertEquals(45.5, $score);
    }

    public function test_calculate_total_score_without_ml(): void
    {
        $ruleResults = ['total_score' => 50];
        $behavioralResults = ['risk_score' => 40];
        $deviceResults = ['risk_score' => 30];

        $score = $this->invokeMethod(
            $this->service,
            'calculateTotalScore',
            [$ruleResults, $behavioralResults, $deviceResults, null]
        );

        // Without ML, scores are redistributed
        // (17.5 + 10 + 6) / 0.8 = 41.875
        $this->assertEqualsWithDelta(41.875, $score, 0.01);
    }

    public function test_make_decision_returns_block_for_blocking_rules(): void
    {
        $ruleResults = [
            'blocking_rules'  => ['BLACKLIST_MATCH'],
            'triggered_rules' => ['BLACKLIST_MATCH'],
        ];

        $decision = $this->invokeMethod($this->service, 'makeDecision', [50.0, 'medium', $ruleResults]);

        $this->assertEquals(FraudScore::DECISION_BLOCK, $decision);
    }

    public function test_make_decision_returns_block_for_high_score(): void
    {
        $ruleResults = ['triggered_rules' => []];

        $decision = $this->invokeMethod($this->service, 'makeDecision', [85.0, 'critical', $ruleResults]);

        $this->assertEquals(FraudScore::DECISION_BLOCK, $decision);
    }

    public function test_make_decision_returns_review_for_medium_high_score(): void
    {
        $ruleResults = ['triggered_rules' => []];

        $decision = $this->invokeMethod($this->service, 'makeDecision', [65.0, 'high', $ruleResults]);

        $this->assertEquals(FraudScore::DECISION_REVIEW, $decision);
    }

    public function test_make_decision_returns_challenge_for_medium_score(): void
    {
        $ruleResults = ['triggered_rules' => []];

        $decision = $this->invokeMethod($this->service, 'makeDecision', [45.0, 'medium', $ruleResults]);

        $this->assertEquals(FraudScore::DECISION_CHALLENGE, $decision);
    }

    public function test_make_decision_returns_allow_for_low_score(): void
    {
        $ruleResults = ['triggered_rules' => []];

        $decision = $this->invokeMethod($this->service, 'makeDecision', [25.0, 'low', $ruleResults]);

        $this->assertEquals(FraudScore::DECISION_ALLOW, $decision);
    }

    public function test_create_score_breakdown_includes_all_components(): void
    {
        $ruleResults = [
            'rule_scores'  => ['VELOCITY_CHECK' => 20, 'AMOUNT_CHECK' => 15],
            'rule_details' => [
                'VELOCITY_CHECK' => ['severity' => 'high'],
                'AMOUNT_CHECK'   => ['severity' => 'medium'],
            ],
        ];
        $behavioralResults = [
            'risk_score'   => 30,
            'risk_factors' => ['unusual_timing'],
        ];
        $deviceResults = [
            'risk_score'   => 25,
            'risk_factors' => ['new_device'],
        ];
        $mlResults = [
            'score'      => 40,
            'confidence' => 0.85,
        ];

        $breakdown = $this->invokeMethod(
            $this->service,
            'createScoreBreakdown',
            [$ruleResults, $behavioralResults, $deviceResults, $mlResults]
        );

        $this->assertCount(5, $breakdown); // 2 rules + behavioral + device + ml

        // Check rule components
        $ruleComponents = array_filter($breakdown, fn ($b) => $b['component'] === 'rule');
        $this->assertCount(2, $ruleComponents);

        // Check behavioral component
        $behavioralComponent = array_filter($breakdown, fn ($b) => $b['component'] === 'behavioral');
        $this->assertCount(1, $behavioralComponent);

        // Check device component
        $deviceComponent = array_filter($breakdown, fn ($b) => $b['component'] === 'device');
        $this->assertCount(1, $deviceComponent);

        // Check ML component
        $mlComponent = array_filter($breakdown, fn ($b) => $b['component'] === 'ml');
        $this->assertCount(1, $mlComponent);
    }

    public function test_extract_network_factors(): void
    {
        $context = [
            'ip_address' => '192.168.1.1',
            'ip_country' => 'US',
            'ip_region'  => 'California',
            'isp'        => 'Comcast',
            'is_vpn'     => true,
            'is_proxy'   => false,
            'is_tor'     => false,
        ];

        $factors = $this->invokeMethod($this->service, 'extractNetworkFactors', [$context]);

        $this->assertEquals('192.168.1.1', $factors['ip_address']);
        $this->assertEquals('US', $factors['ip_country']);
        $this->assertTrue($factors['is_vpn']);
        $this->assertFalse($factors['is_proxy']);
    }

    public function test_analyze_transaction_patterns_detects_rapid_transactions(): void
    {
        $baseTime = time();
        $transactions = [
            ['created_at' => date('Y-m-d H:i:s', $baseTime), 'amount' => 100],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 120), 'amount' => 200],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 240), 'amount' => 150],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 360), 'amount' => 300],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 480), 'amount' => 250],
        ];

        $score = $this->invokeMethod($this->service, 'analyzeTransactionPatterns', [$transactions]);

        // Should detect rapid transactions (4 rapid ones > 3 threshold)
        $this->assertGreaterThan(0, $score);
    }

    public function test_analyze_transaction_patterns_detects_round_amounts(): void
    {
        $baseTime = time();
        $transactions = [
            ['created_at' => date('Y-m-d H:i:s', $baseTime), 'amount' => 1000],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 7200), 'amount' => 2000],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 14400), 'amount' => 500],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 21600), 'amount' => 1500],
            ['created_at' => date('Y-m-d H:i:s', $baseTime - 28800), 'amount' => 3000],
        ];

        $score = $this->invokeMethod($this->service, 'analyzeTransactionPatterns', [$transactions]);

        // Should detect round amounts (100% are round)
        $this->assertGreaterThan(0, $score);
    }

    public function test_get_transaction_indicators_detects_high_value(): void
    {
        // Create a minimal transaction double for testing
        $transaction = new class () extends Transaction {
            public array $event_properties = ['amount' => 15000];

            public function __construct()
            {
                // Skip parent constructor to avoid DB dependencies
            }

            public function __get($key)
            {
                if ($key === 'event_properties') {
                    return $this->event_properties;
                }

                return null;
            }
        };

        $indicators = $this->invokeMethod($this->service, 'getTransactionIndicators', [$transaction]);

        $this->assertContains('high_value_transaction', $indicators);
    }

    public function test_get_transaction_indicators_detects_round_amount(): void
    {
        // Create a minimal transaction double for testing
        $transaction = new class () extends Transaction {
            public array $event_properties = ['amount' => 20000];

            public function __construct()
            {
                // Skip parent constructor to avoid DB dependencies
            }

            public function __get($key)
            {
                if ($key === 'event_properties') {
                    return $this->event_properties;
                }

                return null;
            }
        };

        $indicators = $this->invokeMethod($this->service, 'getTransactionIndicators', [$transaction]);

        $this->assertContains('round_amount', $indicators);
        $this->assertContains('high_value_transaction', $indicators);
    }

    public function test_get_user_indicators_detects_new_account(): void
    {
        $createdAt = Carbon::now()->subDays(15);

        $user = Mockery::mock(User::class)->shouldIgnoreMissing();
        $user->shouldReceive('getAttribute')
            ->with('created_at')
            ->andReturn($createdAt);
        $user->shouldReceive('getAttribute')
            ->with('kyc_level')
            ->andReturn('verified');
        $user->shouldReceive('offsetGet')
            ->with('created_at')
            ->andReturn($createdAt);
        $user->shouldReceive('offsetGet')
            ->with('kyc_level')
            ->andReturn('verified');

        $indicators = $this->invokeMethod($this->service, 'getUserIndicators', [$user]);

        $this->assertContains('new_account', $indicators);
        $this->assertNotContains('no_kyc', $indicators);
    }

    public function test_get_user_indicators_detects_no_kyc(): void
    {
        $createdAt = Carbon::now()->subDays(60);

        $user = Mockery::mock(User::class)->shouldIgnoreMissing();
        $user->shouldReceive('getAttribute')
            ->with('created_at')
            ->andReturn($createdAt);
        $user->shouldReceive('getAttribute')
            ->with('kyc_level')
            ->andReturn('none');
        $user->shouldReceive('offsetGet')
            ->with('created_at')
            ->andReturn($createdAt);
        $user->shouldReceive('offsetGet')
            ->with('kyc_level')
            ->andReturn('none');

        $indicators = $this->invokeMethod($this->service, 'getUserIndicators', [$user]);

        $this->assertContains('no_kyc', $indicators);
        $this->assertNotContains('new_account', $indicators);
    }

    public function test_extract_decision_factors(): void
    {
        $ruleResults = [
            'triggered_rules' => ['rule1', 'rule2', 'rule3'],
            'blocking_rules'  => ['rule1'],
            'rule_scores'     => [
                'rule1' => 30,
                'rule2' => 20,
                'rule3' => 10,
            ],
        ];

        $factors = $this->invokeMethod($this->service, 'extractDecisionFactors', [75.0, $ruleResults]);

        $this->assertEquals(75.0, $factors['total_score']);
        $this->assertEquals(3, $factors['rules_triggered']);
        $this->assertContains('rule1', $factors['blocking_rules']);
        $this->assertArrayHasKey('top_rules', $factors);
    }

    public function test_identify_risk_indicators(): void
    {
        $behavioralData = [
            'unusual_patterns'  => ['pattern1', 'pattern2'],
            'transaction_count' => 75,
        ];

        $indicators = $this->invokeMethod($this->service, 'identifyRiskIndicators', [$behavioralData]);

        $this->assertContains('unusual_patterns_detected', $indicators);
        $this->assertContains('high_transaction_volume', $indicators);
    }

    public function test_generate_recommendations(): void
    {
        $behavioralData = [
            'unusual_patterns' => ['suspicious_timing'],
        ];

        $recommendations = $this->invokeMethod($this->service, 'generateRecommendations', [$behavioralData]);

        $this->assertContains('Review account for suspicious activity', $recommendations);
    }
}
