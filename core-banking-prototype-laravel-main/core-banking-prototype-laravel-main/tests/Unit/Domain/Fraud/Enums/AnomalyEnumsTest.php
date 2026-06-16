<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fraud\Enums;

use App\Domain\Fraud\Enums\AnomalyStatus;
use App\Domain\Fraud\Enums\AnomalyType;
use App\Domain\Fraud\Enums\DetectionMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnomalyEnumsTest extends TestCase
{
    #[Test]
    public function test_anomaly_type_has_all_expected_cases(): void
    {
        $cases = AnomalyType::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('statistical', AnomalyType::Statistical->value);
        $this->assertEquals('behavioral', AnomalyType::Behavioral->value);
        $this->assertEquals('velocity', AnomalyType::Velocity->value);
        $this->assertEquals('geolocation', AnomalyType::Geolocation->value);
    }

    #[Test]
    public function test_anomaly_type_labels(): void
    {
        $this->assertEquals('Statistical Anomaly', AnomalyType::Statistical->label());
        $this->assertEquals('Behavioral Anomaly', AnomalyType::Behavioral->label());
        $this->assertEquals('Velocity Anomaly', AnomalyType::Velocity->label());
        $this->assertEquals('Geolocation Anomaly', AnomalyType::Geolocation->label());
    }

    #[Test]
    public function test_anomaly_status_has_all_expected_cases(): void
    {
        $cases = AnomalyStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertEquals('detected', AnomalyStatus::Detected->value);
        $this->assertEquals('investigating', AnomalyStatus::Investigating->value);
        $this->assertEquals('confirmed', AnomalyStatus::Confirmed->value);
        $this->assertEquals('false_positive', AnomalyStatus::FalsePositive->value);
        $this->assertEquals('resolved', AnomalyStatus::Resolved->value);
    }

    #[Test]
    public function test_anomaly_status_terminal_states(): void
    {
        $this->assertFalse(AnomalyStatus::Detected->isTerminal());
        $this->assertFalse(AnomalyStatus::Investigating->isTerminal());
        $this->assertFalse(AnomalyStatus::Confirmed->isTerminal());
        $this->assertTrue(AnomalyStatus::FalsePositive->isTerminal());
        $this->assertTrue(AnomalyStatus::Resolved->isTerminal());
    }

    #[Test]
    public function test_detection_method_has_all_expected_cases(): void
    {
        $cases = DetectionMethod::cases();

        $this->assertCount(11, $cases);
    }

    #[Test]
    public function test_detection_method_maps_to_correct_anomaly_type(): void
    {
        $this->assertEquals(AnomalyType::Statistical, DetectionMethod::ZScore->anomalyType());
        $this->assertEquals(AnomalyType::Statistical, DetectionMethod::IQR->anomalyType());
        $this->assertEquals(AnomalyType::Statistical, DetectionMethod::IsolationForest->anomalyType());
        $this->assertEquals(AnomalyType::Statistical, DetectionMethod::LOF->anomalyType());

        $this->assertEquals(AnomalyType::Behavioral, DetectionMethod::AdaptiveThreshold->anomalyType());
        $this->assertEquals(AnomalyType::Behavioral, DetectionMethod::DriftDetection->anomalyType());

        $this->assertEquals(AnomalyType::Velocity, DetectionMethod::SlidingWindow->anomalyType());
        $this->assertEquals(AnomalyType::Velocity, DetectionMethod::BurstDetection->anomalyType());

        $this->assertEquals(AnomalyType::Geolocation, DetectionMethod::ImpossibleTravel->anomalyType());
        $this->assertEquals(AnomalyType::Geolocation, DetectionMethod::IpReputation->anomalyType());
        $this->assertEquals(AnomalyType::Geolocation, DetectionMethod::GeoClustering->anomalyType());
    }

    #[Test]
    public function test_detection_method_labels(): void
    {
        $this->assertEquals('Z-Score Analysis', DetectionMethod::ZScore->label());
        $this->assertEquals('Interquartile Range', DetectionMethod::IQR->label());
        $this->assertEquals('Impossible Travel', DetectionMethod::ImpossibleTravel->label());
    }
}
