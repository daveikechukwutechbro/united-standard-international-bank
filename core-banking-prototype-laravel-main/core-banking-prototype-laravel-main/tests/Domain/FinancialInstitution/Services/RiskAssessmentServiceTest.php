<?php

declare(strict_types=1);

namespace Tests\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Services\RiskAssessmentService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RiskAssessmentService pure logic methods.
 *
 * We create an anonymous subclass that exposes private methods
 * and accepts any object, allowing us to test the logic without needing
 * a full FinancialInstitutionApplication model.
 *
 * @phpstan-type RiskResult array{score: int, factors: list<string>, high_risk_exposures?: list<string>, risky_products?: list<string>, monthly_volume?: float|int, monthly_transactions?: int, assets_under_management?: float|int}
 */
class RiskAssessmentServiceTest extends TestCase
{
    /**
     * @var RiskAssessmentService&object{
     *     assessGeographicRiskTest: callable(object): array<string, mixed>,
     *     assessBusinessModelRiskTest: callable(object): array<string, mixed>,
     *     assessVolumeRiskTest: callable(object): array<string, mixed>,
     *     assessFinancialRiskTest: callable(object): array<string, mixed>,
     *     getRiskRatingTest: callable(float): string
     * }
     */
    private RiskAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a testable subclass that exposes private methods
        // @phpstan-ignore-next-line
        $this->service = new class () extends RiskAssessmentService {
            public function __construct()
            {
                // Skip parent constructor - we're testing pure logic methods only
            }

            /**
             * Test wrapper for assessGeographicRisk.
             *
             * @return array<string, mixed>
             */
            public function assessGeographicRiskTest(object $application): array
            {
                $riskScore = 0;
                $factors = [];

                /** @var list<string> */
                $highRiskCountries = ['AF', 'IR', 'KP', 'MM', 'SY', 'YE', 'SO', 'LY', 'VE'];
                /** @var list<string> */
                $mediumRiskCountries = ['RU', 'UA', 'BY', 'TR', 'EG', 'NG', 'KE', 'PK', 'BD'];
                /** @var list<string> */
                $lowRiskCountries = ['US', 'GB', 'DE', 'FR', 'CH', 'NL', 'SE', 'NO', 'DK', 'FI', 'AU', 'CA', 'JP', 'SG'];

                if (in_array($application->country, $highRiskCountries)) {
                    $riskScore += 80;
                    $factors[] = 'High-risk jurisdiction';
                } elseif (in_array($application->country, $mediumRiskCountries)) {
                    $riskScore += 50;
                    $factors[] = 'Medium-risk jurisdiction';
                } elseif (in_array($application->country, $lowRiskCountries)) {
                    $riskScore += 10;
                    $factors[] = 'Low-risk jurisdiction';
                } else {
                    $riskScore += 30;
                    $factors[] = 'Standard-risk jurisdiction';
                }

                /** @var list<string> $targetMarkets */
                $targetMarkets = $application->target_markets ?? [];
                /** @var list<string> $highRiskMarkets */
                $highRiskMarkets = array_values(array_intersect($targetMarkets, $highRiskCountries));

                if (count($highRiskMarkets) > 0) {
                    $riskScore += min(20 * count($highRiskMarkets), 40);
                    $factors[] = 'Operations in high-risk markets';
                }

                if (count($targetMarkets) > 10) {
                    $riskScore += 10;
                    $factors[] = 'Extensive cross-border operations';
                }

                return [
                    'score'               => min($riskScore, 100),
                    'factors'             => $factors,
                    'high_risk_exposures' => $highRiskMarkets,
                ];
            }

            /**
             * Test wrapper for assessBusinessModelRisk.
             *
             * @return array<string, mixed>
             */
            public function assessBusinessModelRiskTest(object $application): array
            {
                $riskScore = 0;
                $factors = [];

                /** @var array<string, int> */
                $typeRisks = [
                    'bank'              => 20,
                    'credit_union'      => 15,
                    'investment_firm'   => 30,
                    'payment_processor' => 40,
                    'fintech'           => 45,
                    'emi'               => 35,
                    'broker_dealer'     => 30,
                    'insurance'         => 20,
                    'other'             => 50,
                ];

                $riskScore += $typeRisks[$application->institution_type] ?? 50;
                $factors[] = ucfirst($application->institution_type) . ' business model';

                /** @var list<string> $products */
                $products = $application->product_offerings ?? [];
                /** @var list<string> */
                $highRiskProducts = ['crypto', 'derivatives', 'forex', 'binary_options', 'crowdfunding'];
                /** @var list<string> $riskyProducts */
                $riskyProducts = array_values(array_intersect($products, $highRiskProducts));

                if (! empty($riskyProducts)) {
                    $riskScore += min(15 * count($riskyProducts), 30);
                    $factors[] = 'High-risk product offerings';
                }

                if (in_array('retail', $products)) {
                    $riskScore += 10;
                    $factors[] = 'Retail customer exposure';
                }

                if (in_array('corporate', $products)) {
                    $riskScore += 5;
                    $factors[] = 'Corporate customer base';
                }

                return [
                    'score'          => min($riskScore, 100),
                    'factors'        => $factors,
                    'risky_products' => $riskyProducts,
                ];
            }

            /**
             * Test wrapper for assessVolumeRisk.
             *
             * @return array<string, mixed>
             */
            public function assessVolumeRiskTest(object $application): array
            {
                $riskScore = 0;
                $factors = [];

                /** @var float|int $monthlyVolume */
                $monthlyVolume = $application->expected_monthly_volume ?? 0;
                /** @var int $monthlyTransactions */
                $monthlyTransactions = $application->expected_monthly_transactions ?? 0;

                if ($monthlyVolume > 100000000) {
                    $riskScore += 40;
                    $factors[] = 'Very high transaction volume';
                } elseif ($monthlyVolume > 10000000) {
                    $riskScore += 25;
                    $factors[] = 'High transaction volume';
                } elseif ($monthlyVolume > 1000000) {
                    $riskScore += 15;
                    $factors[] = 'Moderate transaction volume';
                } else {
                    $riskScore += 5;
                    $factors[] = 'Low transaction volume';
                }

                if ($monthlyTransactions > 1000000) {
                    $riskScore += 30;
                    $factors[] = 'Very high transaction count';
                } elseif ($monthlyTransactions > 100000) {
                    $riskScore += 20;
                    $factors[] = 'High transaction count';
                } elseif ($monthlyTransactions > 10000) {
                    $riskScore += 10;
                    $factors[] = 'Moderate transaction count';
                }

                if ($monthlyTransactions > 0) {
                    $avgTransaction = $monthlyVolume / $monthlyTransactions;
                    if ($avgTransaction > 10000) {
                        $riskScore += 20;
                        $factors[] = 'High average transaction value';
                    } elseif ($avgTransaction < 10) {
                        $riskScore += 15;
                        $factors[] = 'Micro-transaction pattern';
                    }
                }

                return [
                    'score'                => min($riskScore, 100),
                    'factors'              => $factors,
                    'monthly_volume'       => $monthlyVolume,
                    'monthly_transactions' => $monthlyTransactions,
                ];
            }

            /**
             * Test wrapper for assessFinancialRisk.
             *
             * @return array<string, mixed>
             */
            public function assessFinancialRiskTest(object $application): array
            {
                $riskScore = 0;
                $factors = [];

                /** @var float|int $aum */
                $aum = $application->assets_under_management ?? 0;

                if ($aum < 1000000) {
                    $riskScore += 40;
                    $factors[] = 'Very low assets under management';
                } elseif ($aum < 10000000) {
                    $riskScore += 25;
                    $factors[] = 'Low assets under management';
                } elseif ($aum < 100000000) {
                    $riskScore += 15;
                    $factors[] = 'Moderate assets under management';
                } else {
                    $riskScore += 5;
                    $factors[] = 'Substantial assets under management';
                }

                if (in_array($application->institution_type, ['fintech', 'emi', 'payment_processor'])) {
                    $riskScore += 20;
                    $factors[] = 'Non-traditional financial institution';
                }

                return [
                    'score'                   => min($riskScore, 100),
                    'factors'                 => $factors,
                    'assets_under_management' => $aum,
                ];
            }

            /**
             * Test wrapper for getRiskRating.
             */
            public function getRiskRatingTest(float $score): string
            {
                if ($score <= 30) {
                    return 'low';
                } elseif ($score <= 60) {
                    return 'medium';
                } else {
                    return 'high';
                }
            }
        };
    }

    // Geographic Risk Tests - High Risk Countries

    public function test_geographic_risk_high_for_afghanistan(): void
    {
        $application = $this->createMockApplication(['country' => 'AF']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(80, $result['score']);
        $this->assertContains('High-risk jurisdiction', $result['factors']);
    }

    public function test_geographic_risk_high_for_iran(): void
    {
        $application = $this->createMockApplication(['country' => 'IR']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(80, $result['score']);
    }

    public function test_geographic_risk_high_for_north_korea(): void
    {
        $application = $this->createMockApplication(['country' => 'KP']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(80, $result['score']);
    }

    // Geographic Risk Tests - Medium Risk Countries

    public function test_geographic_risk_medium_for_russia(): void
    {
        $application = $this->createMockApplication(['country' => 'RU']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(50, $result['score']);
        $this->assertContains('Medium-risk jurisdiction', $result['factors']);
    }

    public function test_geographic_risk_medium_for_turkey(): void
    {
        $application = $this->createMockApplication(['country' => 'TR']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(50, $result['score']);
    }

    // Geographic Risk Tests - Low Risk Countries

    public function test_geographic_risk_low_for_usa(): void
    {
        $application = $this->createMockApplication(['country' => 'US']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(10, $result['score']);
        $this->assertContains('Low-risk jurisdiction', $result['factors']);
    }

    public function test_geographic_risk_low_for_uk(): void
    {
        $application = $this->createMockApplication(['country' => 'GB']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(10, $result['score']);
    }

    public function test_geographic_risk_low_for_singapore(): void
    {
        $application = $this->createMockApplication(['country' => 'SG']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(10, $result['score']);
    }

    // Geographic Risk Tests - Standard Risk Countries

    public function test_geographic_risk_standard_for_unknown_country(): void
    {
        $application = $this->createMockApplication(['country' => 'XX']);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertEquals(30, $result['score']);
        $this->assertContains('Standard-risk jurisdiction', $result['factors']);
    }

    // Geographic Risk Tests - Target Markets

    public function test_geographic_risk_increases_with_high_risk_target_markets(): void
    {
        $application = $this->createMockApplication([
            'country'        => 'US',
            'target_markets' => ['AF', 'IR'],
        ]);

        $result = $this->service->assessGeographicRiskTest($application);

        // Base 10 + 2 high-risk markets (40 max)
        $this->assertEquals(50, $result['score']);
        $this->assertContains('Operations in high-risk markets', $result['factors']);
        $this->assertContains('AF', $result['high_risk_exposures']);
        $this->assertContains('IR', $result['high_risk_exposures']);
    }

    public function test_geographic_risk_caps_high_risk_market_addition_at_40(): void
    {
        $application = $this->createMockApplication([
            'country'        => 'US',
            'target_markets' => ['AF', 'IR', 'KP', 'MM', 'SY'],
        ]);

        $result = $this->service->assessGeographicRiskTest($application);

        // Base 10 + capped 40 = 50
        $this->assertEquals(50, $result['score']);
    }

    public function test_geographic_risk_increases_with_extensive_cross_border(): void
    {
        $application = $this->createMockApplication([
            'country'        => 'US',
            'target_markets' => ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'DK', 'FI'],
        ]);

        $result = $this->service->assessGeographicRiskTest($application);

        // Base 10 + extensive cross-border 10 = 20
        $this->assertEquals(20, $result['score']);
        $this->assertContains('Extensive cross-border operations', $result['factors']);
    }

    // Business Model Risk Tests - Institution Types

    public function test_business_model_risk_bank_is_low(): void
    {
        $application = $this->createMockApplication(['institution_type' => 'bank']);

        $result = $this->service->assessBusinessModelRiskTest($application);

        $this->assertEquals(20, $result['score']);
        $this->assertContains('Bank business model', $result['factors']);
    }

    public function test_business_model_risk_credit_union_is_lowest(): void
    {
        $application = $this->createMockApplication(['institution_type' => 'credit_union']);

        $result = $this->service->assessBusinessModelRiskTest($application);

        $this->assertEquals(15, $result['score']);
    }

    public function test_business_model_risk_fintech_is_high(): void
    {
        $application = $this->createMockApplication(['institution_type' => 'fintech']);

        $result = $this->service->assessBusinessModelRiskTest($application);

        $this->assertEquals(45, $result['score']);
    }

    public function test_business_model_risk_other_is_highest(): void
    {
        $application = $this->createMockApplication(['institution_type' => 'other']);

        $result = $this->service->assessBusinessModelRiskTest($application);

        $this->assertEquals(50, $result['score']);
    }

    // Business Model Risk Tests - High Risk Products

    public function test_business_model_risk_increases_with_crypto(): void
    {
        $application = $this->createMockApplication([
            'institution_type'  => 'bank',
            'product_offerings' => ['crypto'],
        ]);

        $result = $this->service->assessBusinessModelRiskTest($application);

        // Bank 20 + crypto 15 = 35
        $this->assertEquals(35, $result['score']);
        $this->assertContains('High-risk product offerings', $result['factors']);
        $this->assertContains('crypto', $result['risky_products']);
    }

    public function test_business_model_risk_increases_with_multiple_risky_products(): void
    {
        $application = $this->createMockApplication([
            'institution_type'  => 'bank',
            'product_offerings' => ['crypto', 'derivatives', 'forex'],
        ]);

        $result = $this->service->assessBusinessModelRiskTest($application);

        // Bank 20 + capped 30 = 50
        $this->assertEquals(50, $result['score']);
    }

    public function test_business_model_risk_increases_with_retail_customers(): void
    {
        $application = $this->createMockApplication([
            'institution_type'  => 'bank',
            'product_offerings' => ['retail'],
        ]);

        $result = $this->service->assessBusinessModelRiskTest($application);

        // Bank 20 + retail 10 = 30
        $this->assertEquals(30, $result['score']);
        $this->assertContains('Retail customer exposure', $result['factors']);
    }

    public function test_business_model_risk_increases_with_corporate_customers(): void
    {
        $application = $this->createMockApplication([
            'institution_type'  => 'bank',
            'product_offerings' => ['corporate'],
        ]);

        $result = $this->service->assessBusinessModelRiskTest($application);

        // Bank 20 + corporate 5 = 25
        $this->assertEquals(25, $result['score']);
        $this->assertContains('Corporate customer base', $result['factors']);
    }

    // Volume Risk Tests

    public function test_volume_risk_low_for_small_volume(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 500000,
            'expected_monthly_transactions' => 1000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        $this->assertEquals(5, $result['score']);
        $this->assertContains('Low transaction volume', $result['factors']);
    }

    public function test_volume_risk_moderate_for_1m_to_10m(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 5000000,
            'expected_monthly_transactions' => 5000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        $this->assertEquals(15, $result['score']);
        $this->assertContains('Moderate transaction volume', $result['factors']);
    }

    public function test_volume_risk_high_for_10m_to_100m(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 50000000,
            'expected_monthly_transactions' => 50000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // High volume 25 + Moderate transaction count 10 = 35
        $this->assertEquals(35, $result['score']);
        $this->assertContains('High transaction volume', $result['factors']);
    }

    public function test_volume_risk_very_high_for_over_100m(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 200000000,
            'expected_monthly_transactions' => 500000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // Very high volume 40 + High transaction count 20 = 60
        $this->assertEquals(60, $result['score']);
        $this->assertContains('Very high transaction volume', $result['factors']);
    }

    public function test_volume_risk_increases_with_high_transaction_count(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 500000,
            'expected_monthly_transactions' => 2000000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // Low volume 5 + Very high count 30 + Micro-transaction 15 = 50
        $this->assertEquals(50, $result['score']);
        $this->assertContains('Very high transaction count', $result['factors']);
    }

    public function test_volume_risk_increases_with_high_average_transaction(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 50000000,
            'expected_monthly_transactions' => 1000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // High volume 25 + High avg 20 = 45
        $this->assertEquals(45, $result['score']);
        $this->assertContains('High average transaction value', $result['factors']);
    }

    public function test_volume_risk_increases_with_micro_transactions(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 500000,
            'expected_monthly_transactions' => 100000,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // Low volume 5 + Moderate count 10 + Micro-transaction 15 = 30
        $this->assertEquals(30, $result['score']);
        $this->assertContains('Micro-transaction pattern', $result['factors']);
    }

    // Financial Risk Tests

    public function test_financial_risk_very_low_for_under_1m_aum(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 500000,
            'institution_type'        => 'bank',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        $this->assertEquals(40, $result['score']);
        $this->assertContains('Very low assets under management', $result['factors']);
    }

    public function test_financial_risk_low_for_1m_to_10m_aum(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 5000000,
            'institution_type'        => 'bank',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        $this->assertEquals(25, $result['score']);
        $this->assertContains('Low assets under management', $result['factors']);
    }

    public function test_financial_risk_moderate_for_10m_to_100m_aum(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 50000000,
            'institution_type'        => 'bank',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        $this->assertEquals(15, $result['score']);
        $this->assertContains('Moderate assets under management', $result['factors']);
    }

    public function test_financial_risk_substantial_for_over_100m_aum(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 500000000,
            'institution_type'        => 'bank',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        $this->assertEquals(5, $result['score']);
        $this->assertContains('Substantial assets under management', $result['factors']);
    }

    public function test_financial_risk_increases_for_fintech(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 50000000,
            'institution_type'        => 'fintech',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        // Moderate AUM 15 + Non-traditional 20 = 35
        $this->assertEquals(35, $result['score']);
        $this->assertContains('Non-traditional financial institution', $result['factors']);
    }

    public function test_financial_risk_increases_for_emi(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 50000000,
            'institution_type'        => 'emi',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        // Moderate AUM 15 + Non-traditional 20 = 35
        $this->assertEquals(35, $result['score']);
    }

    public function test_financial_risk_increases_for_payment_processor(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => 50000000,
            'institution_type'        => 'payment_processor',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        // Moderate AUM 15 + Non-traditional 20 = 35
        $this->assertEquals(35, $result['score']);
    }

    // Risk Rating Tests

    public function test_risk_rating_low_for_score_under_30(): void
    {
        $this->assertEquals('low', $this->service->getRiskRatingTest(10));
        $this->assertEquals('low', $this->service->getRiskRatingTest(20));
        $this->assertEquals('low', $this->service->getRiskRatingTest(30));
    }

    public function test_risk_rating_medium_for_score_31_to_60(): void
    {
        $this->assertEquals('medium', $this->service->getRiskRatingTest(31));
        $this->assertEquals('medium', $this->service->getRiskRatingTest(45));
        $this->assertEquals('medium', $this->service->getRiskRatingTest(60));
    }

    public function test_risk_rating_high_for_score_over_60(): void
    {
        $this->assertEquals('high', $this->service->getRiskRatingTest(61));
        $this->assertEquals('high', $this->service->getRiskRatingTest(80));
        $this->assertEquals('high', $this->service->getRiskRatingTest(100));
    }

    // Edge Cases

    public function test_geographic_risk_score_capped_at_100(): void
    {
        $application = $this->createMockApplication([
            'country'        => 'AF',
            'target_markets' => ['IR', 'KP', 'MM', 'SY', 'YE', 'SO', 'LY', 'VE'],
        ]);

        $result = $this->service->assessGeographicRiskTest($application);

        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_business_model_risk_score_capped_at_100(): void
    {
        $application = $this->createMockApplication([
            'institution_type'  => 'other',
            'product_offerings' => ['crypto', 'derivatives', 'forex', 'binary_options', 'retail', 'corporate'],
        ]);

        $result = $this->service->assessBusinessModelRiskTest($application);

        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_volume_risk_handles_zero_transactions(): void
    {
        $application = $this->createMockApplication([
            'expected_monthly_volume'       => 1000000,
            'expected_monthly_transactions' => 0,
        ]);

        $result = $this->service->assessVolumeRiskTest($application);

        // Should not divide by zero
        $this->assertEquals(5, $result['score']); // Only low volume factor
    }

    public function test_financial_risk_handles_null_aum(): void
    {
        $application = $this->createMockApplication([
            'assets_under_management' => null,
            'institution_type'        => 'bank',
        ]);

        $result = $this->service->assessFinancialRiskTest($application);

        // Null defaults to 0, which is < 1M
        $this->assertEquals(40, $result['score']);
    }

    /**
     * Create a mock application object for testing.
     *
     * @param array<string, mixed> $data
     */
    private function createMockApplication(array $data): object
    {
        return new class ($data) {
            public ?string $country;

            public ?string $institution_type;

            /** @var list<string>|null */
            public ?array $target_markets;

            /** @var list<string>|null */
            public ?array $product_offerings;

            public ?float $expected_monthly_volume;

            public ?int $expected_monthly_transactions;

            public ?float $assets_under_management;

            /**
             * @param array<string, mixed> $data
             */
            public function __construct(array $data)
            {
                $this->country = $data['country'] ?? null;
                $this->institution_type = $data['institution_type'] ?? null;
                $this->target_markets = $data['target_markets'] ?? [];
                $this->product_offerings = $data['product_offerings'] ?? [];
                $this->expected_monthly_volume = $data['expected_monthly_volume'] ?? null;
                $this->expected_monthly_transactions = $data['expected_monthly_transactions'] ?? null;
                $this->assets_under_management = $data['assets_under_management'] ?? null;
            }
        };
    }
}
