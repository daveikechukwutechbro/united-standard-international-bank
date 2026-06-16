<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fraud\Services;

use App\Domain\Fraud\Models\AnomalyDetection;
use App\Domain\Fraud\Services\GeoMathService;
use App\Domain\Fraud\Services\StatisticalAnalysisService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edge case and boundary tests for fraud anomaly detection services.
 */
class AnomalyEdgeCaseTest extends TestCase
{
    // ---- Statistical Analysis Edge Cases ----

    #[Test]
    public function z_score_with_zero_std_dev_does_not_divide_by_zero(): void
    {
        $service = new StatisticalAnalysisService();

        $user = \App\Models\User::factory()->create();
        $profile = \App\Domain\Fraud\Models\BehavioralProfile::create([
            'user_id'                     => $user->id,
            'avg_transaction_amount'      => 1000,
            'transaction_amount_std_dev'  => 0, // zero std dev
            'is_established'              => true,
            'total_transaction_count'     => 100,
            'avg_daily_transaction_count' => 0, // zero avg daily count
            'max_daily_volume'            => 0, // zero max daily
            'typical_transaction_times'   => array_fill(0, 24, 4.17),
            'typical_transaction_days'    => array_fill(0, 7, 14.29),
            'common_locations'            => [],
            'location_history'            => [],
            'trusted_devices'             => [],
        ]);

        $result = $service->zScoreAnalysis(['amount' => 5000], $profile);

        $this->assertFalse($result['detected']);
        $this->assertIsFloat($result['score']);
        $this->assertIsFloat($result['confidence']);
    }

    #[Test]
    public function iqr_with_identical_values_has_zero_iqr(): void
    {
        $service = new StatisticalAnalysisService();
        $history = array_map(fn () => ['amount' => 100], range(0, 19));

        $result = $service->iqrAnalysis([
            'amount'              => 100,
            'transaction_history' => $history,
        ], null);

        // With zero IQR, everything equals Q1=Q3, bounds collapse
        $this->assertFalse($result['detected']);
        $this->assertEquals(0.0, $result['score']);
    }

    #[Test]
    public function iqr_with_negative_amount_below_lower_bound(): void
    {
        $service = new StatisticalAnalysisService();
        $history = array_map(fn ($i) => ['amount' => 500 + $i * 10], range(0, 29));

        $result = $service->iqrAnalysis([
            'amount'              => -1000,
            'transaction_history' => $history,
        ], null);

        $this->assertTrue($result['detected']);
        $this->assertGreaterThan(0, $result['score']);
    }

    #[Test]
    public function iqr_with_zero_amount(): void
    {
        $service = new StatisticalAnalysisService();
        $history = array_map(fn ($i) => ['amount' => 500 + $i * 10], range(0, 29));

        $result = $service->iqrAnalysis([
            'amount'              => 0,
            'transaction_history' => $history,
        ], null);

        $this->assertTrue($result['detected']);
        $this->assertLessThan($result['details']['lower_bound'], 0);
    }

    #[Test]
    public function isolation_forest_with_zero_amount(): void
    {
        $service = new StatisticalAnalysisService();

        $result = $service->isolationForestAnalysis(['amount' => 0]);

        // amount <= 0 means no amount_log feature extracted
        $this->assertFalse($result['detected']);
        $this->assertEquals('no_features', $result['details']['reason'] ?? null);
    }

    #[Test]
    public function isolation_forest_with_single_feature(): void
    {
        $service = new StatisticalAnalysisService();

        $result = $service->isolationForestAnalysis(['amount' => 1000]);

        $this->assertIsFloat($result['score']);
        $this->assertEquals(1, $result['details']['feature_count']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    #[Test]
    public function lof_with_all_identical_history_values(): void
    {
        $service = new StatisticalAnalysisService();
        $history = array_map(fn () => ['amount' => 500], range(0, 24));

        $result = $service->localOutlierFactorAnalysis([
            'amount'              => 500, // same as all history
            'transaction_history' => $history,
        ], null);

        $this->assertIsFloat($result['score']);
        $this->assertArrayHasKey('lof_score', $result['details']);
    }

    // ---- GeoMath Edge Cases ----

    #[Test]
    public function haversine_distance_at_poles(): void
    {
        $service = new GeoMathService();

        // North pole to south pole ≈ 20015 km (half Earth circumference)
        $distance = $service->haversineDistance(90.0, 0.0, -90.0, 0.0);

        $this->assertEqualsWithDelta(20015, $distance, 50);
    }

    #[Test]
    public function haversine_distance_across_date_line(): void
    {
        $service = new GeoMathService();

        // Two points near the international date line
        $distance = $service->haversineDistance(0.0, 179.0, 0.0, -179.0);

        // Should be about 222 km, NOT the long way around
        $this->assertLessThan(300, $distance);
    }

    #[Test]
    public function haversine_distance_antipodal_points(): void
    {
        $service = new GeoMathService();

        // Diametrically opposite points ≈ 20015 km
        $distance = $service->haversineDistance(0.0, 0.0, 0.0, 180.0);

        $this->assertEqualsWithDelta(20015, $distance, 50);
    }

    #[Test]
    public function cluster_locations_with_empty_array(): void
    {
        $service = new GeoMathService();

        $result = $service->clusterLocations([]);

        $this->assertArrayHasKey('clusters', $result);
        $this->assertCount(0, $result['clusters']);
    }

    #[Test]
    public function cluster_locations_with_single_point(): void
    {
        $service = new GeoMathService();

        $result = $service->clusterLocations([['lat' => 40.0, 'lon' => -74.0]]);

        $this->assertCount(0, $result['clusters']);
        $this->assertCount(1, $result['noise']);
    }

    #[Test]
    public function cluster_locations_respects_max_points_config(): void
    {
        $service = new GeoMathService();

        // Generate more points than default max (1000)
        config(['fraud.geolocation.geo_cluster.max_points' => 5]);
        $points = array_map(fn ($i) => [
            'lat' => 40.7 + $i * 0.001,
            'lon' => -74.0,
        ], range(0, 9));

        $result = $service->clusterLocations($points);

        // Should process without error - points were truncated to 5
        $this->assertArrayHasKey('clusters', $result);
    }

    #[Test]
    public function distance_to_nearest_cluster_with_empty_clusters(): void
    {
        $service = new GeoMathService();

        $result = $service->distanceToNearestCluster(40.0, -74.0, []);

        $this->assertTrue($result['outside_cluster']);
        $this->assertEquals(INF, $result['distance_km']);
    }

    #[Test]
    public function impossible_travel_with_negative_time(): void
    {
        $service = new GeoMathService();

        // Negative time should still flag as impossible if distance > 0
        $result = $service->isImpossibleTravel(40.7128, -74.0060, 51.5074, -0.1278, -100);

        $this->assertTrue($result['impossible']);
    }

    // ---- Severity Boundary Tests ----

    #[Test]
    public function calculate_severity_at_exact_boundaries(): void
    {
        $this->assertEquals('low', AnomalyDetection::calculateSeverity(0.0));
        $this->assertEquals('low', AnomalyDetection::calculateSeverity(39.99));
        $this->assertEquals('medium', AnomalyDetection::calculateSeverity(40.0));
        $this->assertEquals('medium', AnomalyDetection::calculateSeverity(59.99));
        $this->assertEquals('high', AnomalyDetection::calculateSeverity(60.0));
        $this->assertEquals('high', AnomalyDetection::calculateSeverity(79.99));
        $this->assertEquals('critical', AnomalyDetection::calculateSeverity(80.0));
        $this->assertEquals('critical', AnomalyDetection::calculateSeverity(100.0));
    }

    #[Test]
    public function calculate_severity_with_negative_score(): void
    {
        // Negative scores should map to 'low'
        $this->assertEquals('low', AnomalyDetection::calculateSeverity(-10.0));
    }

    #[Test]
    public function calculate_severity_with_score_above_100(): void
    {
        // Scores > 100 should still be 'critical'
        $this->assertEquals('critical', AnomalyDetection::calculateSeverity(150.0));
    }

    // ---- Null/Empty Input Handling ----

    #[Test]
    public function statistical_analyze_with_empty_context(): void
    {
        $service = new StatisticalAnalysisService();

        $results = $service->analyze([], null);

        $this->assertArrayHasKey('z_score', $results);
        $this->assertArrayHasKey('iqr', $results);
        $this->assertArrayHasKey('isolation_forest', $results);
        $this->assertArrayHasKey('lof', $results);

        // All should be non-detected with empty context
        foreach ($results as $result) {
            $this->assertFalse($result['detected']);
        }
    }

    #[Test]
    public function z_score_with_null_profile_fields(): void
    {
        $service = new StatisticalAnalysisService();

        $user = \App\Models\User::factory()->create();
        $profile = \App\Domain\Fraud\Models\BehavioralProfile::create([
            'user_id'                     => $user->id,
            'avg_transaction_amount'      => 0,
            'transaction_amount_std_dev'  => 0,
            'is_established'              => true,
            'total_transaction_count'     => 0,
            'avg_daily_transaction_count' => 0,
            'max_daily_volume'            => 0,
            'typical_transaction_times'   => [],
            'typical_transaction_days'    => [],
            'common_locations'            => [],
            'location_history'            => [],
            'trusted_devices'             => [],
        ]);

        $result = $service->zScoreAnalysis(['amount' => 1000], $profile);

        $this->assertFalse($result['detected']);
        $this->assertIsFloat($result['score']);
    }

    #[Test]
    public function seasonal_decomposition_with_missing_time_distribution(): void
    {
        $service = new StatisticalAnalysisService();

        $user = \App\Models\User::factory()->create();
        $profile = \App\Domain\Fraud\Models\BehavioralProfile::create([
            'user_id'                   => $user->id,
            'is_established'            => true,
            'total_transaction_count'   => 100,
            'typical_transaction_times' => [], // empty
            'typical_transaction_days'  => [], // empty
            'common_locations'          => [],
            'location_history'          => [],
            'trusted_devices'           => [],
        ]);

        $result = $service->seasonalDecomposition(['hour_of_day' => 12, 'day_of_week' => 3], $profile);

        // Empty distribution means 0% for all hours/days -> high anomaly
        $this->assertTrue($result['detected']);
    }
}
