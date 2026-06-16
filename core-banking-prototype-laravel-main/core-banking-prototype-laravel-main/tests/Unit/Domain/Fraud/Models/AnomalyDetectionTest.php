<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fraud\Models;

use App\Domain\Fraud\Enums\AnomalyStatus;
use App\Domain\Fraud\Enums\AnomalyType;
use App\Domain\Fraud\Enums\DetectionMethod;
use App\Domain\Fraud\Models\AnomalyDetection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnomalyDetectionTest extends TestCase
{
    #[Test]
    public function test_can_create_anomaly_detection_via_factory(): void
    {
        $anomaly = AnomalyDetection::factory()->create();

        $this->assertNotNull($anomaly->id);
        $this->assertInstanceOf(AnomalyType::class, $anomaly->anomaly_type);
        $this->assertInstanceOf(DetectionMethod::class, $anomaly->detection_method);
        $this->assertInstanceOf(AnomalyStatus::class, $anomaly->status);
    }

    #[Test]
    public function test_statistical_factory_state(): void
    {
        $anomaly = AnomalyDetection::factory()->statistical()->create();

        $this->assertEquals(AnomalyType::Statistical, $anomaly->anomaly_type);
        $this->assertEquals(DetectionMethod::ZScore, $anomaly->detection_method);
    }

    #[Test]
    public function test_critical_factory_state(): void
    {
        $anomaly = AnomalyDetection::factory()->critical()->create();

        $this->assertEquals('critical', $anomaly->severity);
        $this->assertGreaterThanOrEqual(80, (float) $anomaly->anomaly_score);
    }

    #[Test]
    public function test_calculate_severity(): void
    {
        $this->assertEquals('low', AnomalyDetection::calculateSeverity(20.0));
        $this->assertEquals('medium', AnomalyDetection::calculateSeverity(45.0));
        $this->assertEquals('high', AnomalyDetection::calculateSeverity(65.0));
        $this->assertEquals('critical', AnomalyDetection::calculateSeverity(90.0));
    }

    #[Test]
    public function test_is_critical(): void
    {
        $anomaly = AnomalyDetection::factory()->create(['severity' => 'critical']);
        $this->assertTrue($anomaly->isCritical());

        $anomaly = AnomalyDetection::factory()->create(['severity' => 'high']);
        $this->assertFalse($anomaly->isCritical());
    }

    #[Test]
    public function test_is_high_severity(): void
    {
        $this->assertTrue(AnomalyDetection::factory()->create(['severity' => 'critical'])->isHighSeverity());
        $this->assertTrue(AnomalyDetection::factory()->create(['severity' => 'high'])->isHighSeverity());
        $this->assertFalse(AnomalyDetection::factory()->create(['severity' => 'medium'])->isHighSeverity());
        $this->assertFalse(AnomalyDetection::factory()->create(['severity' => 'low'])->isHighSeverity());
    }

    #[Test]
    public function test_is_active(): void
    {
        $detected = AnomalyDetection::factory()->create(['status' => AnomalyStatus::Detected]);
        $this->assertTrue($detected->isActive());

        $investigating = AnomalyDetection::factory()->create(['status' => AnomalyStatus::Investigating]);
        $this->assertTrue($investigating->isActive());

        $resolved = AnomalyDetection::factory()->create(['status' => AnomalyStatus::Resolved]);
        $this->assertFalse($resolved->isActive());

        $falsePositive = AnomalyDetection::factory()->create(['status' => AnomalyStatus::FalsePositive]);
        $this->assertFalse($falsePositive->isActive());
    }

    #[Test]
    public function test_false_positive_factory_state(): void
    {
        $anomaly = AnomalyDetection::factory()->falsePositive()->create();

        $this->assertEquals(AnomalyStatus::FalsePositive, $anomaly->status);
        $this->assertEquals('false_positive', $anomaly->feedback_outcome);
        $this->assertNotNull($anomaly->feedback_notes);
    }
}
