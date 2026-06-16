<?php

declare(strict_types=1);

namespace Tests\Domain\Treasury\ValueObjects;

use App\Domain\Treasury\ValueObjects\LiquidityMetrics;
use InvalidArgumentException;
use Tests\UnitTestCase;

class LiquidityMetricsTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_valid_values(): void
    {
        $metrics = new LiquidityMetrics(
            liquidityCoverageRatio: 1.2,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 45,
            probabilityOfShortage: 0.03,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        expect($metrics->liquidityCoverageRatio)->toBe(1.2);
        expect($metrics->netStableFundingRatio)->toBe(1.1);
        expect($metrics->stressTestSurvivalDays)->toBe(45);
        expect($metrics->probabilityOfShortage)->toBe(0.03);
        expect($metrics->valueAtRisk95)->toBe(50000.0);
        expect($metrics->expectedShortfall)->toBe(75000.0);
        expect($metrics->liquidityBufferAdequacy)->toBe(0.85);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_liquidity_coverage_ratio(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: -0.1,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 0.05,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Liquidity Coverage Ratio cannot be negative');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_net_stable_funding_ratio(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: -0.5,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 0.05,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Net Stable Funding Ratio cannot be negative');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_stress_test_survival_days(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: -1,
            probabilityOfShortage: 0.05,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Stress Test Survival Days cannot be negative');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_probability_of_shortage_out_of_range(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 1.5,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Probability of Shortage must be between 0 and 1');

        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: -0.1,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Probability of Shortage must be between 0 and 1');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_value_at_risk(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 0.05,
            valueAtRisk95: -1000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Value at Risk cannot be negative');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_expected_shortfall(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 0.05,
            valueAtRisk95: 10000.0,
            expectedShortfall: -5000.0,
            liquidityBufferAdequacy: 0.8
        ))->toThrow(InvalidArgumentException::class, 'Expected Shortfall cannot be negative');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_liquidity_buffer_adequacy_out_of_range(): void
    {
        expect(fn () => new LiquidityMetrics(
            liquidityCoverageRatio: 1.0,
            netStableFundingRatio: 1.0,
            stressTestSurvivalDays: 30,
            probabilityOfShortage: 0.05,
            valueAtRisk95: 10000.0,
            expectedShortfall: 15000.0,
            liquidityBufferAdequacy: 1.5
        ))->toThrow(InvalidArgumentException::class, 'Liquidity Buffer Adequacy must be between 0 and 1');
    }

    // ===========================================
    // isHealthy Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_healthy_metrics(): void
    {
        $healthy = new LiquidityMetrics(
            liquidityCoverageRatio: 1.2,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 45,
            probabilityOfShortage: 0.03,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        expect($healthy->isHealthy())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_unhealthy_low_liquidity_coverage(): void
    {
        $unhealthy = new LiquidityMetrics(
            liquidityCoverageRatio: 0.9,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 45,
            probabilityOfShortage: 0.03,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        expect($unhealthy->isHealthy())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_unhealthy_low_stress_survival(): void
    {
        $unhealthy = new LiquidityMetrics(
            liquidityCoverageRatio: 1.2,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 20,
            probabilityOfShortage: 0.03,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        expect($unhealthy->isHealthy())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_unhealthy_high_probability_of_shortage(): void
    {
        $unhealthy = new LiquidityMetrics(
            liquidityCoverageRatio: 1.2,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 45,
            probabilityOfShortage: 0.10,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        expect($unhealthy->isHealthy())->toBeFalse();
    }

    // ===========================================
    // getRiskLevel Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_low_risk_for_strong_metrics(): void
    {
        $strong = new LiquidityMetrics(
            liquidityCoverageRatio: 1.5,
            netStableFundingRatio: 1.3,
            stressTestSurvivalDays: 60,
            probabilityOfShortage: 0.02,
            valueAtRisk95: 30000.0,
            expectedShortfall: 45000.0,
            liquidityBufferAdequacy: 0.9
        );

        expect($strong->getRiskLevel())->toBe('low');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_medium_risk_for_moderate_metrics(): void
    {
        $moderate = new LiquidityMetrics(
            liquidityCoverageRatio: 1.1,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 40,
            probabilityOfShortage: 0.04,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.8
        );

        expect($moderate->getRiskLevel())->toBe('medium');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_high_risk_for_weak_metrics(): void
    {
        $weak = new LiquidityMetrics(
            liquidityCoverageRatio: 0.8,
            netStableFundingRatio: 0.9,
            stressTestSurvivalDays: 20,
            probabilityOfShortage: 0.15,
            valueAtRisk95: 100000.0,
            expectedShortfall: 150000.0,
            liquidityBufferAdequacy: 0.5
        );

        expect($weak->getRiskLevel())->toBe('high');
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $metrics = new LiquidityMetrics(
            liquidityCoverageRatio: 1.2,
            netStableFundingRatio: 1.1,
            stressTestSurvivalDays: 45,
            probabilityOfShortage: 0.03,
            valueAtRisk95: 50000.0,
            expectedShortfall: 75000.0,
            liquidityBufferAdequacy: 0.85
        );

        $array = $metrics->toArray();

        expect($array)->toHaveKeys([
            'liquidity_coverage_ratio',
            'net_stable_funding_ratio',
            'stress_test_survival_days',
            'probability_of_shortage',
            'value_at_risk_95',
            'expected_shortfall',
            'liquidity_buffer_adequacy',
            'is_healthy',
            'risk_level',
        ]);
        expect($array['liquidity_coverage_ratio'])->toBe(1.2);
        expect($array['is_healthy'])->toBeTrue();
        expect($array['risk_level'])->toBe('low');
    }

    // ===========================================
    // fromArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array(): void
    {
        $data = [
            'liquidity_coverage_ratio'  => 1.2,
            'net_stable_funding_ratio'  => 1.1,
            'stress_test_survival_days' => 45,
            'probability_of_shortage'   => 0.03,
            'value_at_risk_95'          => 50000.0,
            'expected_shortfall'        => 75000.0,
            'liquidity_buffer_adequacy' => 0.85,
        ];

        $metrics = LiquidityMetrics::fromArray($data);

        expect($metrics->liquidityCoverageRatio)->toBe(1.2);
        expect($metrics->netStableFundingRatio)->toBe(1.1);
        expect($metrics->stressTestSurvivalDays)->toBe(45);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_defaults_for_missing_array_keys(): void
    {
        $metrics = LiquidityMetrics::fromArray([]);

        expect($metrics->liquidityCoverageRatio)->toBe(0.0);
        expect($metrics->stressTestSurvivalDays)->toBe(0);
    }
}
