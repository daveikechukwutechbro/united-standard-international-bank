<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\MultiTenancy\TenantProvisioningService;
use App\Services\MultiTenancy\TenantUsageMeteringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that enforces tenant plan limits on API requests.
 *
 * Checks the current tenant's usage against their plan limits
 * and rejects requests when limits are exceeded.
 */
class EnforceTenantPlanLimits
{
    public function __construct(
        private readonly TenantUsageMeteringService $metering,
        private readonly TenantProvisioningService $provisioning,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();

        if ($tenant === null) {
            return $next($request);
        }

        $planLimits = $this->provisioning->getTenantConfig($tenant);
        $result = $this->metering->checkPlanLimits($tenant, $planLimits);

        if ($result['exceeded']) {
            return response()->json([
                'error'      => 'Plan limit exceeded',
                'violations' => $result['violations'],
            ], 429);
        }

        // Record the API call
        $this->metering->recordApiCall($tenant, $request->path());

        return $next($request);
    }
}
