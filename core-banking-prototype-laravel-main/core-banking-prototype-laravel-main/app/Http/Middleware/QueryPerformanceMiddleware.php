<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryPerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $queryLoggingEnabled = (bool) config('performance.query_logging', false);

        if ($queryLoggingEnabled) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        if ($queryLoggingEnabled) {
            $this->analyzeQueries($request, $response);
        }

        return $response;
    }

    /**
     * Analyze recorded queries for performance issues.
     */
    private function analyzeQueries(Request $request, Response $response): void
    {
        $queries = DB::getQueryLog();
        $totalQueries = count($queries);
        $totalTimeMs = 0.0;
        $slowQueryThreshold = (float) config('performance.slow_query_threshold_ms', 100);
        $nPlusOneDetection = (bool) config('performance.n_plus_one_detection', true);
        $nPlusOneThreshold = (int) config('performance.n_plus_one_threshold', 5);
        $addHeaders = (bool) config('performance.add_headers', false);

        $queryCounts = [];

        foreach ($queries as $query) {
            $time = (float) ($query['time'] ?? 0);
            $totalTimeMs += $time;

            // Detect slow queries
            if ($time > $slowQueryThreshold) {
                Log::warning('Slow query detected', [
                    'sql'      => $query['query'],
                    'time_ms'  => $time,
                    'bindings' => $query['bindings'] ?? [],
                    'url'      => request()->fullUrl(),
                ]);
            }

            // Group queries by SQL string for N+1 detection
            if ($nPlusOneDetection) {
                $sql = $query['query'];
                if (! isset($queryCounts[$sql])) {
                    $queryCounts[$sql] = 0;
                }
                $queryCounts[$sql]++;
            }
        }

        // Detect N+1 patterns
        if ($nPlusOneDetection) {
            foreach ($queryCounts as $sql => $count) {
                if ($count > $nPlusOneThreshold) {
                    Log::warning('Potential N+1 query detected', [
                        'sql'       => $sql,
                        'count'     => $count,
                        'threshold' => $nPlusOneThreshold,
                        'url'       => request()->fullUrl(),
                    ]);
                }
            }
        }

        // Add response headers in non-production environments
        if ($addHeaders && ! app()->environment('production')) {
            $response->headers->set('X-Query-Count', (string) $totalQueries);
            $response->headers->set('X-Query-Time-Ms', (string) round($totalTimeMs, 2));
        }

        // Clean up query log
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
}
