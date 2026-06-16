<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Services;

use App\Domain\Fraud\Enums\AnomalyStatus;
use App\Domain\Fraud\Enums\AnomalyType;
use App\Domain\Fraud\Enums\DetectionMethod;
use App\Domain\Fraud\Events\AnomalyDetected;
use App\Domain\Fraud\Models\AnomalyDetection;
use App\Domain\Fraud\Models\BehavioralProfile;
use Exception;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionOrchestrator
{
    private const SCORE_THRESHOLD = 40.0;

    public function __construct(
        private readonly StatisticalAnalysisService $statisticalService,
        private readonly BehavioralAnalysisService $behavioralService,
        private readonly RuleEngineService $ruleEngineService,
        private readonly DeviceFingerprintService $deviceService,
        private readonly GeoMathService $geoMathService,
    ) {
    }

    /**
     * Run all anomaly detection activities against the given context.
     *
     * @return array{anomalies: array, highest_score: float, has_critical: bool}
     */
    public function detectAnomalies(array $context, ?string $entityId = null, ?string $entityType = null, ?int $userId = null, ?string $fraudScoreId = null): array
    {
        if (! config('fraud.anomaly_detection.enabled', false)) {
            return [
                'anomalies'     => [],
                'highest_score' => 0.0,
                'has_critical'  => false,
            ];
        }

        // Validate and sanitize context inputs
        $context = $this->sanitizeContext($context);

        // Look up behavioral profile for the user
        $profile = $userId ? BehavioralProfile::where('user_id', $userId)->first() : null;

        $results = [];
        $highestScore = 0.0;
        $hasCritical = false;

        // 1. Statistical anomaly detection
        $statistical = $this->runStatisticalDetection($context, $profile);
        if ($statistical) {
            $results[] = $statistical;
            $highestScore = max($highestScore, $statistical['score']);
        }

        // 2. Behavioral anomaly detection
        $behavioral = $this->runBehavioralDetection($context, $profile);
        if ($behavioral) {
            $results[] = $behavioral;
            $highestScore = max($highestScore, $behavioral['score']);
        }

        // 3. Velocity anomaly detection
        $velocity = $this->runVelocityDetection($context);
        if ($velocity) {
            $results[] = $velocity;
            $highestScore = max($highestScore, $velocity['score']);
        }

        // 4. Geolocation anomaly detection
        $geolocation = $this->runGeolocationDetection($context);
        if ($geolocation) {
            $results[] = $geolocation;
            $highestScore = max($highestScore, $geolocation['score']);
        }

        // Persist detections that exceed threshold
        $persisted = [];
        foreach ($results as $result) {
            if ($result['score'] >= self::SCORE_THRESHOLD) {
                $detection = $this->persistDetection(
                    $result,
                    $context,
                    $entityId,
                    $entityType,
                    $userId,
                    $fraudScoreId,
                );

                if ($detection) {
                    $persisted[] = $detection;

                    if ($detection->severity === 'critical') {
                        $hasCritical = true;
                    }

                    AnomalyDetected::dispatch($detection);
                }
            }
        }

        return [
            'anomalies'     => $results,
            'highest_score' => round($highestScore, 2),
            'has_critical'  => $hasCritical,
            'persisted'     => count($persisted),
        ];
    }

    /**
     * Run statistical anomaly detection.
     */
    protected function runStatisticalDetection(array $context, ?BehavioralProfile $profile): ?array
    {
        try {
            $result = $this->statisticalService->analyze($context, $profile);
            $highestScore = 0.0;
            $highestMethod = null;

            foreach ($result as $method => $detection) {
                $score = $detection['score'] ?? $detection['anomaly_score'] ?? 0;
                if ($score > $highestScore) {
                    $highestScore = (float) $score;
                    $highestMethod = $method;
                }
            }

            if ($highestScore <= 0) {
                return null;
            }

            return [
                'anomaly_type'     => AnomalyType::Statistical,
                'detection_method' => $this->resolveStatisticalMethod($highestMethod),
                'score'            => round($highestScore, 2),
                'confidence'       => $this->calculateConfidence($highestScore, $result),
                'details'          => $result,
            ];
        } catch (Exception $e) {
            Log::warning('Statistical anomaly detection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Run behavioral anomaly detection.
     */
    protected function runBehavioralDetection(array $context, ?BehavioralProfile $profile): ?array
    {
        try {
            if (! $profile || ! $profile->is_established) {
                return null;
            }

            // Compute adaptive thresholds and check for breaches
            $thresholds = $this->behavioralService->computeAdaptiveThresholds($profile);
            $breaches = $this->detectThresholdBreaches($context, $thresholds);

            $adaptiveScore = min(count($breaches) * 25.0, 80.0);

            // Drift detection using recent transaction history
            $recentTransactions = $context['transaction_history'] ?? [];
            $driftResult = ! empty($recentTransactions)
                ? $this->behavioralService->detectDrift($profile, $recentTransactions)
                : ['drifted' => false, 'drift_score' => 0.0, 'details' => []];

            $driftScore = (float) ($driftResult['drift_score'] ?? 0) * 100;
            $highestScore = max($adaptiveScore, $driftScore);

            if ($highestScore <= 0) {
                return null;
            }

            $method = $adaptiveScore >= $driftScore
                ? DetectionMethod::AdaptiveThreshold
                : DetectionMethod::DriftDetection;

            return [
                'anomaly_type'     => AnomalyType::Behavioral,
                'detection_method' => $method,
                'score'            => round($highestScore, 2),
                'confidence'       => $this->calculateConfidence($highestScore, ['adaptive' => $thresholds, 'drift' => $driftResult]),
                'details'          => [
                    'adaptive_thresholds' => $thresholds,
                    'breaches'            => $breaches,
                    'drift_detection'     => $driftResult,
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Behavioral anomaly detection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Run velocity anomaly detection.
     */
    protected function runVelocityDetection(array $context): ?array
    {
        try {
            $slidingWindows = $this->ruleEngineService->evaluateSlidingWindows($context);
            $burstResult = $this->ruleEngineService->detectBurst($context);

            $windowScore = 0.0;
            foreach ($slidingWindows['breaches'] ?? [] as $breach) {
                $windowScore = max($windowScore, (float) ($breach['ratio'] ?? 1.0) * 40);
            }

            $burstScore = ($burstResult['is_burst'] ?? false) ? min((float) ($burstResult['burst_ratio'] ?? 1.0) * 30, 80.0) : 0.0;
            $highestScore = max($windowScore, $burstScore);

            if ($highestScore <= 0) {
                return null;
            }

            $method = $windowScore >= $burstScore
                ? DetectionMethod::SlidingWindow
                : DetectionMethod::BurstDetection;

            return [
                'anomaly_type'     => AnomalyType::Velocity,
                'detection_method' => $method,
                'score'            => round(min($highestScore, 100.0), 2),
                'confidence'       => $this->calculateConfidence($highestScore, ['windows' => $slidingWindows, 'burst' => $burstResult]),
                'details'          => [
                    'sliding_windows' => $slidingWindows,
                    'burst_detection' => $burstResult,
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Velocity anomaly detection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Run geolocation anomaly detection.
     */
    protected function runGeolocationDetection(array $context): ?array
    {
        try {
            $highestScore = 0.0;
            $highestMethod = null;
            $details = [];

            // Impossible travel
            if (isset($context['lat'], $context['lon'], $context['last_lat'], $context['last_lon'], $context['time_diff_seconds'])) {
                $travelResult = $this->geoMathService->isImpossibleTravel(
                    $context['last_lat'],
                    $context['last_lon'],
                    $context['lat'],
                    $context['lon'],
                    $context['time_diff_seconds'],
                );

                $score = $travelResult['impossible'] ? 85.0 : 0.0;
                $details['impossible_travel'] = $travelResult;

                if ($score > $highestScore) {
                    $highestScore = $score;
                    $highestMethod = DetectionMethod::ImpossibleTravel;
                }
            }

            // IP reputation
            if (isset($context['ip'])) {
                $ipResult = $this->deviceService->assessIpReputation($context['ip']);
                $details['ip_reputation'] = $ipResult;

                if ((float) $ipResult['risk_score'] > $highestScore) {
                    $highestScore = (float) $ipResult['risk_score'];
                    $highestMethod = DetectionMethod::IpReputation;
                }
            }

            // Geo-clustering
            if (isset($context['lat'], $context['lon'], $context['location_history']) && count($context['location_history']) >= 3) {
                $clusterResult = $this->geoMathService->clusterLocations($context['location_history']);

                if (! empty($clusterResult['clusters'])) {
                    $distResult = $this->geoMathService->distanceToNearestCluster(
                        $context['lat'],
                        $context['lon'],
                        $clusterResult['clusters'],
                    );

                    $details['geo_clustering'] = array_merge($clusterResult, ['distance_check' => $distResult]);

                    if ($distResult['outside_cluster']) {
                        $clusterScore = round(min(($distResult['distance_km'] / 500.0) * 40, 80.0), 2);
                        if ($clusterScore > $highestScore) {
                            $highestScore = $clusterScore;
                            $highestMethod = DetectionMethod::GeoClustering;
                        }
                    }
                }
            }

            if ($highestScore <= 0 || ! $highestMethod) {
                return null;
            }

            return [
                'anomaly_type'     => AnomalyType::Geolocation,
                'detection_method' => $highestMethod,
                'score'            => round($highestScore, 2),
                'confidence'       => $this->calculateConfidence($highestScore, $details),
                'details'          => $details,
            ];
        } catch (Exception $e) {
            Log::warning('Geolocation anomaly detection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Detect which adaptive thresholds are breached by the current context.
     *
     * @return array<int, array{metric: string, value: float, threshold: float}>
     */
    protected function detectThresholdBreaches(array $context, array $thresholds): array
    {
        $breaches = [];
        $amount = (float) ($context['amount'] ?? 0);

        if ($amount > ($thresholds['amount_upper'] ?? PHP_FLOAT_MAX)) {
            $breaches[] = ['metric' => 'amount_high', 'value' => $amount, 'threshold' => $thresholds['amount_upper']];
        }

        if ($amount > 0 && $amount < ($thresholds['amount_lower'] ?? 0)) {
            $breaches[] = ['metric' => 'amount_low', 'value' => $amount, 'threshold' => $thresholds['amount_lower']];
        }

        $dailyCount = (int) ($context['daily_transaction_count'] ?? 0);
        if ($dailyCount > ($thresholds['daily_count_max'] ?? PHP_INT_MAX)) {
            $breaches[] = ['metric' => 'daily_count', 'value' => (float) $dailyCount, 'threshold' => (float) $thresholds['daily_count_max']];
        }

        $dailyVolume = (float) ($context['daily_transaction_volume'] ?? 0);
        if ($dailyVolume > ($thresholds['daily_volume_max'] ?? PHP_FLOAT_MAX)) {
            $breaches[] = ['metric' => 'daily_volume', 'value' => $dailyVolume, 'threshold' => $thresholds['daily_volume_max']];
        }

        return $breaches;
    }

    /**
     * Persist an anomaly detection record.
     */
    protected function persistDetection(
        array $result,
        array $context,
        ?string $entityId,
        ?string $entityType,
        ?int $userId,
        ?string $fraudScoreId,
    ): ?AnomalyDetection {
        try {
            $score = $result['score'];

            return AnomalyDetection::create([
                'entity_id'        => $entityId,
                'entity_type'      => $entityType,
                'user_id'          => $userId,
                'anomaly_type'     => $result['anomaly_type'],
                'detection_method' => $result['detection_method'],
                'status'           => AnomalyStatus::Detected,
                'anomaly_score'    => $score,
                'confidence'       => $result['confidence'],
                'severity'         => AnomalyDetection::calculateSeverity($score),
                'features'         => $result['details'] ?? [],
                'explanation'      => $this->generateExplanation($result),
                'context_snapshot' => [
                    'amount'                   => $context['amount'] ?? null,
                    'type'                     => $context['type'] ?? null,
                    'ip_hash'                  => isset($context['ip']) ? hash('sha256', $context['ip']) : null,
                    'ip_country'               => $context['ip_country'] ?? null,
                    'daily_transaction_count'  => $context['daily_transaction_count'] ?? null,
                    'daily_transaction_volume' => $context['daily_transaction_volume'] ?? null,
                ],
                'is_real_time'   => true,
                'fraud_score_id' => $fraudScoreId,
                'model_version'  => config('fraud.anomaly_detection.model_version', '1.0.0'),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to persist anomaly detection', [
                'error'  => $e->getMessage(),
                'result' => $result,
            ]);

            return null;
        }
    }

    /**
     * Calculate confidence based on score strength and supporting evidence.
     */
    protected function calculateConfidence(float $score, array $details): float
    {
        // Base confidence from score clarity
        $confidence = match (true) {
            $score >= 80 => 0.95,
            $score >= 60 => 0.85,
            $score >= 40 => 0.70,
            default      => 0.50,
        };

        // Boost confidence if multiple signals agree
        $signalCount = count(array_filter($details, fn ($d) => is_array($d) && ! empty($d)));
        if ($signalCount >= 3) {
            $confidence = min($confidence + 0.05, 1.0);
        }

        return round($confidence, 4);
    }

    /**
     * Generate a human-readable explanation for the detection.
     */
    protected function generateExplanation(array $result): array
    {
        $type = $result['anomaly_type']->label();
        $method = $result['detection_method']->value;
        $score = $result['score'];

        return [
            'summary' => "{$type} anomaly detected via {$method} with score {$score}",
            'type'    => $type,
            'method'  => $method,
            'score'   => $score,
        ];
    }

    /**
     * Sanitize and validate context inputs to prevent invalid calculations.
     */
    protected function sanitizeContext(array $context): array
    {
        // Clamp lat/lon to valid ranges
        if (isset($context['lat'])) {
            $context['lat'] = max(-90.0, min(90.0, (float) $context['lat']));
        }
        if (isset($context['lon'])) {
            $context['lon'] = max(-180.0, min(180.0, (float) $context['lon']));
        }
        if (isset($context['last_lat'])) {
            $context['last_lat'] = max(-90.0, min(90.0, (float) $context['last_lat']));
        }
        if (isset($context['last_lon'])) {
            $context['last_lon'] = max(-180.0, min(180.0, (float) $context['last_lon']));
        }

        // Ensure non-negative time difference
        if (isset($context['time_diff_seconds'])) {
            $context['time_diff_seconds'] = max(0, (int) $context['time_diff_seconds']);
        }

        // Ensure non-negative amount
        if (isset($context['amount']) && is_numeric($context['amount']) && $context['amount'] < 0) {
            $context['amount'] = 0;
        }

        // Bound location_history size
        if (isset($context['location_history']) && is_array($context['location_history'])) {
            $maxPoints = (int) config('fraud.geolocation.geo_cluster.max_points', 1000);
            if (count($context['location_history']) > $maxPoints) {
                $context['location_history'] = array_slice($context['location_history'], -$maxPoints);
            }
        }

        // Bound transaction_history size
        if (isset($context['transaction_history']) && is_array($context['transaction_history'])) {
            $maxHistory = (int) config('fraud.statistical.max_history_size', 1000);
            if (count($context['transaction_history']) > $maxHistory) {
                $context['transaction_history'] = array_slice($context['transaction_history'], -$maxHistory);
            }
        }

        return $context;
    }

    /**
     * Resolve statistical method enum from string key.
     */
    protected function resolveStatisticalMethod(?string $method): DetectionMethod
    {
        return match ($method) {
            'z_score'          => DetectionMethod::ZScore,
            'iqr'              => DetectionMethod::IQR,
            'isolation_forest' => DetectionMethod::IsolationForest,
            'lof'              => DetectionMethod::LOF,
            default            => DetectionMethod::ZScore,
        };
    }
}
