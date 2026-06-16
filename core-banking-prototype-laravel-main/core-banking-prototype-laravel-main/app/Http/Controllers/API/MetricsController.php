<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Monitoring\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Throwable;

/**
 * Business-domain metrics and lightweight health check.
 *
 * Exposes domain-specific counters (JIT funding, circuit breakers, ZK proofs)
 * in Prometheus exposition format and a fast /health endpoint for load balancers.
 */
class MetricsController extends Controller
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {
    }

    #[OA\Get(
        path: '/api/metrics/prometheus',
        operationId: 'getDomainPrometheusMetrics',
        tags: ['Metrics'],
        summary: 'Export domain metrics in Prometheus format',
        description: 'Returns domain-specific business metrics formatted for Prometheus scraping',
    )]
    #[OA\Response(
        response: 200,
        description: 'Prometheus metrics exported',
        content: new OA\MediaType(
            mediaType: 'text/plain',
            schema: new OA\Schema(type: 'string'),
        ),
    )]
    public function prometheus(): Response
    {
        $metrics = $this->metrics->getMetrics();
        $namespace = (string) config('monitoring.metrics.prometheus.namespace', 'finaegis');
        $output = '';

        foreach ($metrics as $name => $value) {
            $fullName = $namespace . '_' . $name;
            $type = str_contains($name, '_ms') ? 'gauge' : (str_contains($name, '_total') || str_contains($name, '_approvals') || str_contains($name, '_declines') || str_contains($name, '_trips') ? 'counter' : 'gauge');
            $output .= "# HELP {$fullName} Domain metric: {$name}\n";
            $output .= "# TYPE {$fullName} {$type}\n";
            $output .= "{$fullName} {$value}\n";
        }

        return response($output, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    #[OA\Get(
        path: '/api/health',
        operationId: 'getQuickHealthStatus',
        tags: ['Metrics'],
        summary: 'Quick health check for load balancers',
        description: 'Returns lightweight health status checking database and cache connectivity',
    )]
    #[OA\Response(
        response: 200,
        description: 'Application is healthy',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'string', enum: ['healthy', 'degraded']),
            new OA\Property(property: 'checks', type: 'object'),
            new OA\Property(property: 'version', type: 'string'),
            new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
        ]),
    )]
    #[OA\Response(
        response: 503,
        description: 'Application is degraded',
    )]
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'app'      => true,
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status'    => $healthy ? 'healthy' : 'degraded',
            'checks'    => $checks,
            'version'   => '7.1.0',
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 10);

            return (bool) Cache::get('health_check', false);
        } catch (Throwable) {
            return false;
        }
    }
}
