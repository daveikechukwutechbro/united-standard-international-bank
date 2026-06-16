<?php

declare(strict_types=1);

namespace Tests\Unit\MultiTenancy;

use App\Http\Middleware\EnforceTenantPlanLimits;
use App\Models\Tenant;
use App\Services\MultiTenancy\TenantProvisioningService;
use App\Services\MultiTenancy\TenantUsageMeteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

class EnforceTenantPlanLimitsTest extends TestCase
{
    /** @var TenantUsageMeteringService&MockInterface */
    private TenantUsageMeteringService $metering;

    /** @var TenantProvisioningService&MockInterface */
    private TenantProvisioningService $provisioning;

    private EnforceTenantPlanLimits $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TenantUsageMeteringService&MockInterface $metering */
        $metering = Mockery::mock(TenantUsageMeteringService::class);
        $this->metering = $metering;

        /** @var TenantProvisioningService&MockInterface $provisioning */
        $provisioning = Mockery::mock(TenantProvisioningService::class);
        $this->provisioning = $provisioning;
        $this->middleware = new EnforceTenantPlanLimits($this->metering, $this->provisioning);
    }

    #[Test]
    public function it_passes_through_when_no_tenant(): void
    {
        // Ensure no tenant is bound
        $this->app->forgetInstance(TenantContract::class);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_blocks_when_plan_limits_exceeded(): void
    {
        /** @var Tenant&MockInterface $tenant */
        $tenant = Mockery::mock(Tenant::class);
        $tenant->shouldReceive('getTenantKey')->andReturn('tenant-123');

        // Bind tenant to the contract so tenant() helper returns it
        $this->app->instance(TenantContract::class, $tenant);

        $this->provisioning->shouldReceive('getTenantConfig')
            ->with($tenant)
            ->andReturn(['max_api_calls' => 1000, 'max_users' => 5]);

        $this->metering->shouldReceive('checkPlanLimits')
            ->with($tenant, ['max_api_calls' => 1000, 'max_users' => 5])
            ->andReturn([
                'exceeded'   => true,
                'violations' => [
                    'api_calls' => ['current' => 1000, 'limit' => 1000],
                ],
            ]);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertEquals(429, $response->getStatusCode());

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);
        $this->assertEquals('Plan limit exceeded', $data['error']);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function it_allows_request_and_records_api_call_when_within_limits(): void
    {
        /** @var Tenant&MockInterface $tenant */
        $tenant = Mockery::mock(Tenant::class);
        $tenant->shouldReceive('getTenantKey')->andReturn('tenant-456');

        // Bind tenant to the contract so tenant() helper returns it
        $this->app->instance(TenantContract::class, $tenant);

        $this->provisioning->shouldReceive('getTenantConfig')
            ->with($tenant)
            ->andReturn(['max_api_calls' => 1000, 'max_users' => 5]);

        $this->metering->shouldReceive('checkPlanLimits')
            ->with($tenant, ['max_api_calls' => 1000, 'max_users' => 5])
            ->andReturn([
                'exceeded'   => false,
                'violations' => [],
            ]);

        $this->metering->shouldReceive('recordApiCall')
            ->with($tenant, 'api/test')
            ->once();

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
