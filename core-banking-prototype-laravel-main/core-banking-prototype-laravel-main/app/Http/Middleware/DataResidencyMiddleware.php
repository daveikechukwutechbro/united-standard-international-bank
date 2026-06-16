<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Compliance\Services\Certification\GeoRoutingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DataResidencyMiddleware
{
    public function __construct(
        private readonly GeoRoutingService $geoRoutingService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('compliance-certification.data_residency.enabled', false)) {
            return $next($request);
        }

        $region = $this->geoRoutingService->resolveRegion($request);

        // Set region in request attributes for downstream use
        $request->attributes->set('data_region', $region);

        $response = $next($request);

        // Add region header to response
        $response->headers->set('X-Data-Region', $region);

        // Log cross-region access if configured
        $userRegion = $request->header('X-User-Region');
        if ($userRegion && strtoupper($userRegion) !== $region) {
            Log::info('Cross-region access detected', [
                'user_region' => strtoupper($userRegion),
                'data_region' => $region,
                'path'        => $request->path(),
                'ip'          => $request->ip(),
            ]);
        }

        return $response;
    }
}
