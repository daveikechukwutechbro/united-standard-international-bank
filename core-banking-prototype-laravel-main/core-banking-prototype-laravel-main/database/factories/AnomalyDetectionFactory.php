<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Account\Models\Transaction;
use App\Domain\Fraud\Enums\AnomalyStatus;
use App\Domain\Fraud\Enums\AnomalyType;
use App\Domain\Fraud\Enums\DetectionMethod;
use App\Domain\Fraud\Models\AnomalyDetection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Fraud\Models\AnomalyDetection>
 */
class AnomalyDetectionFactory extends Factory
{
    protected $model = AnomalyDetection::class;

    public function definition(): array
    {
        $anomalyType = $this->faker->randomElement(AnomalyType::cases());
        $detectionMethod = $this->getMethodForType($anomalyType);
        $score = $this->faker->randomFloat(2, 10, 95);

        return [
            'entity_id'         => $this->faker->uuid(),
            'entity_type'       => Transaction::class,
            'user_id'           => User::factory(),
            'anomaly_type'      => $anomalyType,
            'detection_method'  => $detectionMethod,
            'status'            => AnomalyStatus::Detected,
            'anomaly_score'     => $score,
            'confidence'        => $this->faker->randomFloat(4, 0.5, 0.99),
            'severity'          => AnomalyDetection::calculateSeverity($score),
            'features'          => ['amount' => $this->faker->randomFloat(2, 100, 50000)],
            'thresholds'        => ['z_score' => 3.0, 'iqr_multiplier' => 1.5],
            'explanation'       => ['reason' => 'Test anomaly detection'],
            'raw_scores'        => ['z_score' => $this->faker->randomFloat(4, 2.0, 5.0)],
            'context_snapshot'  => ['ip' => $this->faker->ipv4()],
            'baseline_snapshot' => ['avg_amount' => $this->faker->randomFloat(2, 500, 5000)],
            'model_version'     => 'v1.0',
            'pipeline_run_id'   => $this->faker->uuid(),
            'is_real_time'      => true,
            'fraud_score_id'    => null,
            'fraud_case_id'     => null,
            'feedback_outcome'  => null,
            'feedback_notes'    => null,
        ];
    }

    private function getMethodForType(AnomalyType $type): DetectionMethod
    {
        return match ($type) {
            AnomalyType::Statistical => $this->faker->randomElement([
                DetectionMethod::ZScore, DetectionMethod::IQR,
                DetectionMethod::IsolationForest, DetectionMethod::LOF,
            ]),
            AnomalyType::Behavioral => $this->faker->randomElement([
                DetectionMethod::AdaptiveThreshold, DetectionMethod::DriftDetection,
            ]),
            AnomalyType::Velocity => $this->faker->randomElement([
                DetectionMethod::SlidingWindow, DetectionMethod::BurstDetection,
            ]),
            AnomalyType::Geolocation => $this->faker->randomElement([
                DetectionMethod::ImpossibleTravel, DetectionMethod::IpReputation,
                DetectionMethod::GeoClustering,
            ]),
        };
    }

    public function statistical(): static
    {
        return $this->state(fn () => [
            'anomaly_type'     => AnomalyType::Statistical,
            'detection_method' => DetectionMethod::ZScore,
        ]);
    }

    public function behavioral(): static
    {
        return $this->state(fn () => [
            'anomaly_type'     => AnomalyType::Behavioral,
            'detection_method' => DetectionMethod::AdaptiveThreshold,
        ]);
    }

    public function velocity(): static
    {
        return $this->state(fn () => [
            'anomaly_type'     => AnomalyType::Velocity,
            'detection_method' => DetectionMethod::SlidingWindow,
        ]);
    }

    public function geolocation(): static
    {
        return $this->state(fn () => [
            'anomaly_type'     => AnomalyType::Geolocation,
            'detection_method' => DetectionMethod::ImpossibleTravel,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'anomaly_score' => $this->faker->randomFloat(2, 80, 100),
            'severity'      => 'critical',
            'confidence'    => $this->faker->randomFloat(4, 0.9, 0.99),
        ]);
    }

    public function falsePositive(): static
    {
        return $this->state(fn () => [
            'status'           => AnomalyStatus::FalsePositive,
            'feedback_outcome' => 'false_positive',
            'feedback_notes'   => 'Confirmed as false positive during review.',
        ]);
    }
}
