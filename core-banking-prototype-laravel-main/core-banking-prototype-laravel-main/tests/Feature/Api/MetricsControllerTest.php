<?php

declare(strict_types=1);

use App\Infrastructure\Monitoring\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
});

describe('GET /api/health', function () {
    it('returns healthy status with 200', function () {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database',
                    'cache',
                    'app',
                ],
                'version',
                'timestamp',
            ])
            ->assertJson([
                'status'  => 'healthy',
                'version' => '7.1.0',
            ]);
    });

    it('includes all check results as booleans', function () {
        $response = $this->getJson('/api/health');

        $checks = $response->json('checks');
        expect($checks['database'])->toBeBool();
        expect($checks['cache'])->toBeBool();
        expect($checks['app'])->toBeTrue();
    });

    it('returns ISO 8601 timestamp', function () {
        $response = $this->getJson('/api/health');

        $timestamp = $response->json('timestamp');
        expect($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });
});

describe('GET /api/metrics/prometheus', function () {
    it('returns text/plain content type with prometheus version', function () {
        $response = $this->get('/api/metrics/prometheus');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        expect($contentType)->toContain('text/plain');
        expect($contentType)->toContain('version=0.0.4');
    });

    it('returns prometheus exposition format with HELP and TYPE lines', function () {
        // Prime some metrics
        $service = app(MetricsService::class);
        $service->increment('jit_funding_approved', 5);
        $service->timing('jit_funding_latency', 123.4);

        $response = $this->get('/api/metrics/prometheus');

        $content = $response->getContent();
        expect($content)->toContain('# HELP');
        expect($content)->toContain('# TYPE');
        expect($content)->toContain('finaegis_jit_funding_approvals');
        expect($content)->toContain('finaegis_jit_funding_latency_ms');
    });

    it('returns all expected metric names', function () {
        $response = $this->get('/api/metrics/prometheus');

        $content = $response->getContent();
        expect($content)->toContain('finaegis_jit_funding_latency_ms');
        expect($content)->toContain('finaegis_jit_funding_approvals');
        expect($content)->toContain('finaegis_jit_funding_declines');
        expect($content)->toContain('finaegis_api_requests_total');
        expect($content)->toContain('finaegis_graphql_queries_total');
        expect($content)->toContain('finaegis_bridge_transactions_total');
        expect($content)->toContain('finaegis_circuit_breaker_trips');
        expect($content)->toContain('finaegis_zk_proof_generation_ms');
    });

    it('uses configurable namespace prefix', function () {
        config(['monitoring.metrics.prometheus.namespace' => 'custom_ns']);

        $response = $this->get('/api/metrics/prometheus');

        $content = $response->getContent();
        expect($content)->toContain('custom_ns_jit_funding_approvals');
        expect($content)->not->toContain('finaegis_jit_funding_approvals');
    });

    it('returns zero values when no metrics recorded', function () {
        $response = $this->get('/api/metrics/prometheus');

        $content = $response->getContent();
        expect($content)->toContain('finaegis_jit_funding_approvals 0');
        expect($content)->toContain('finaegis_circuit_breaker_trips 0');
    });
});
