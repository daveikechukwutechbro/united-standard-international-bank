<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Monitoring;

use App\Infrastructure\Monitoring\MetricsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MetricsServiceTest extends TestCase
{
    private MetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->service = new MetricsService();
    }

    public function test_increment_creates_counter(): void
    {
        $this->service->increment('test_counter');

        $this->assertEquals(1, Cache::get('metrics:test_counter:default'));
    }

    public function test_increment_adds_to_existing_counter(): void
    {
        $this->service->increment('test_counter');
        $this->service->increment('test_counter');
        $this->service->increment('test_counter', 3);

        $this->assertEquals(5, Cache::get('metrics:test_counter:default'));
    }

    public function test_increment_with_tags(): void
    {
        $this->service->increment('requests', 1, ['method' => 'GET']);
        $this->service->increment('requests', 1, ['method' => 'POST']);
        $this->service->increment('requests', 1, ['method' => 'GET']);

        $this->assertEquals(2, Cache::get('metrics:requests:method=GET'));
        $this->assertEquals(1, Cache::get('metrics:requests:method=POST'));
    }

    public function test_gauge_sets_value(): void
    {
        $this->service->gauge('cpu_usage', 72.5);

        $this->assertEquals(72.5, Cache::get('metrics:cpu_usage:default'));
    }

    public function test_gauge_overwrites_previous_value(): void
    {
        $this->service->gauge('cpu_usage', 72.5);
        $this->service->gauge('cpu_usage', 85.3);

        $this->assertEquals(85.3, Cache::get('metrics:cpu_usage:default'));
    }

    public function test_timing_records_milliseconds_and_count(): void
    {
        $this->service->timing('api_latency', 123.45);

        $this->assertEquals(123.45, Cache::get('metrics:timing:api_latency:default'));
        $this->assertEquals(1, Cache::get('metrics:count:api_latency:default'));
    }

    public function test_timing_updates_latest_value(): void
    {
        $this->service->timing('api_latency', 100.0);
        $this->service->timing('api_latency', 200.0);

        $this->assertEquals(200.0, Cache::get('metrics:timing:api_latency:default'));
        $this->assertEquals(2, Cache::get('metrics:count:api_latency:default'));
    }

    public function test_get_metrics_returns_all_expected_keys(): void
    {
        $metrics = $this->service->getMetrics();

        $this->assertArrayHasKey('jit_funding_latency_ms', $metrics);
        $this->assertArrayHasKey('jit_funding_approvals', $metrics);
        $this->assertArrayHasKey('jit_funding_declines', $metrics);
        $this->assertArrayHasKey('api_requests_total', $metrics);
        $this->assertArrayHasKey('graphql_queries_total', $metrics);
        $this->assertArrayHasKey('bridge_transactions_total', $metrics);
        $this->assertArrayHasKey('circuit_breaker_trips', $metrics);
        $this->assertArrayHasKey('zk_proof_generation_ms', $metrics);
    }

    public function test_get_metrics_returns_zeros_when_no_data(): void
    {
        $metrics = $this->service->getMetrics();

        foreach ($metrics as $value) {
            $this->assertEquals(0, $value);
        }
    }

    public function test_get_metrics_reflects_recorded_data(): void
    {
        $this->service->increment('jit_funding_approved', 10);
        $this->service->increment('jit_funding_declined', 3);
        $this->service->timing('jit_funding_latency', 42.5);
        $this->service->increment('circuit_breaker_trip', 2);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(10, $metrics['jit_funding_approvals']);
        $this->assertEquals(3, $metrics['jit_funding_declines']);
        $this->assertEquals(42.5, $metrics['jit_funding_latency_ms']);
        $this->assertEquals(2, $metrics['circuit_breaker_trips']);
    }

    public function test_tags_with_multiple_keys(): void
    {
        $this->service->increment('requests', 1, ['method' => 'GET', 'status' => '200']);

        $this->assertEquals(1, Cache::get('metrics:requests:method=GET:status=200'));
    }

    public function test_empty_tags_use_default_key(): void
    {
        $this->service->increment('requests', 1, []);

        $this->assertEquals(1, Cache::get('metrics:requests:default'));
    }
}
