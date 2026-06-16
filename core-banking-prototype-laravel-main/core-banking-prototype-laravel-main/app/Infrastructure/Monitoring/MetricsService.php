<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralized metrics service for domain-specific application metrics.
 *
 * Stores counters, gauges, and timing data in cache for Prometheus scraping.
 * Complements the existing MetricsCollector (HTTP/queue/cache) by tracking
 * business-domain metrics: JIT funding, circuit breakers, bridge transactions,
 * ZK proof generation, and GraphQL queries.
 */
class MetricsService
{
    private const CACHE_PREFIX = 'metrics:';

    private const TTL = 3600;

    /**
     * Increment a counter metric by the given value.
     *
     * @param array<string, string> $tags
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        $key = self::CACHE_PREFIX . $metric . ':' . $this->tagsToKey($tags);

        try {
            $current = (int) Cache::get($key, 0);
            Cache::put($key, $current + $value, self::TTL);
        } catch (Throwable $e) {
            Log::warning('MetricsService: Failed to increment metric', [
                'metric' => $metric,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Set a gauge metric to the given value.
     *
     * @param array<string, string> $tags
     */
    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $key = self::CACHE_PREFIX . $metric . ':' . $this->tagsToKey($tags);

        try {
            Cache::put($key, $value, self::TTL);
        } catch (Throwable $e) {
            Log::warning('MetricsService: Failed to set gauge', [
                'metric' => $metric,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record a timing measurement in milliseconds.
     *
     * @param array<string, string> $tags
     */
    public function timing(string $metric, float $milliseconds, array $tags = []): void
    {
        $tagKey = $this->tagsToKey($tags);
        $key = self::CACHE_PREFIX . 'timing:' . $metric . ':' . $tagKey;
        $countKey = self::CACHE_PREFIX . 'count:' . $metric . ':' . $tagKey;

        try {
            Cache::put($key, $milliseconds, self::TTL);
            $current = (int) Cache::get($countKey, 0);
            Cache::put($countKey, $current + 1, self::TTL);
        } catch (Throwable $e) {
            Log::warning('MetricsService: Failed to record timing', [
                'metric' => $metric,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return all tracked metrics for Prometheus export.
     *
     * @return array<string, float|int>
     */
    public function getMetrics(): array
    {
        return [
            'jit_funding_latency_ms'    => $this->getTimingMetric('jit_funding_latency'),
            'jit_funding_approvals'     => $this->getCounter('jit_funding_approved'),
            'jit_funding_declines'      => $this->getCounter('jit_funding_declined'),
            'api_requests_total'        => $this->getCounter('api_requests'),
            'graphql_queries_total'     => $this->getCounter('graphql_queries'),
            'bridge_transactions_total' => $this->getCounter('bridge_transactions'),
            'circuit_breaker_trips'     => $this->getCounter('circuit_breaker_trip'),
            'zk_proof_generation_ms'    => $this->getTimingMetric('zk_proof_generation'),
        ];
    }

    /**
     * Convert tag pairs into a cache-safe key suffix.
     *
     * @param array<string, string> $tags
     */
    private function tagsToKey(array $tags): string
    {
        if (empty($tags)) {
            return 'default';
        }

        return implode(':', array_map(
            fn (string $k, string $v): string => "{$k}={$v}",
            array_keys($tags),
            array_values($tags),
        ));
    }

    private function getCounter(string $metric): int
    {
        return (int) Cache::get(self::CACHE_PREFIX . $metric . ':default', 0);
    }

    private function getTimingMetric(string $metric): float
    {
        return (float) Cache::get(self::CACHE_PREFIX . 'timing:' . $metric . ':default', 0);
    }
}
