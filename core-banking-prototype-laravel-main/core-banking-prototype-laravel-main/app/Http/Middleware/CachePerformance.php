<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CachePerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Track cache performance metrics
        $cacheHits = Cache::get('cache_performance:hits', 0);
        $cacheMisses = Cache::get('cache_performance:misses', 0);

        // Store current values in request for later comparison
        $request->attributes->set('cache_hits_start', $cacheHits);
        $request->attributes->set('cache_misses_start', $cacheMisses);

        $response = $next($request);

        // Calculate hit rate for this request
        $newHits = Cache::get('cache_performance:hits', 0);
        $newMisses = Cache::get('cache_performance:misses', 0);

        $requestHits = $newHits - $cacheHits;
        $requestMisses = $newMisses - $cacheMisses;
        $totalRequests = $requestHits + $requestMisses;

        if ($totalRequests > 0) {
            $hitRate = ($requestHits / $totalRequests) * 100;

            // Add cache performance headers
            $response->headers->set('X-Cache-Hits', (string) $requestHits);
            $response->headers->set('X-Cache-Misses', (string) $requestMisses);
            $response->headers->set('X-Cache-Hit-Rate', sprintf('%.2f%%', $hitRate));

            // Log if hit rate is low
            if ($hitRate < 50 && $totalRequests > 5) {
                Log::warning(
                    'Low cache hit rate detected',
                    [
                        'endpoint' => $request->path(),
                        'hit_rate' => $hitRate,
                        'hits'     => $requestHits,
                        'misses'   => $requestMisses,
                    ]
                );
            }
        }

        return $response;
    }
}
