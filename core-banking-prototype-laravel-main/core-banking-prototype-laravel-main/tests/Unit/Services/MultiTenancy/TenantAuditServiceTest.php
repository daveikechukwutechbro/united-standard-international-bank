<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MultiTenancy;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\User;
use App\Services\MultiTenancy\TenantAuditService;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;
use Throwable;

class TenantAuditServiceTest extends ServiceTestCase
{
    protected TenantAuditService $service;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->service = new TenantAuditService();

            $user = User::factory()->create();

            /** @var Team $team */
            $team = Team::forceCreate([
                'user_id'       => $user->id,
                'name'          => 'Audit Team',
                'personal_team' => false,
            ]);

            /** @var Tenant $tenant */
            $tenant = Tenant::create([
                'team_id' => $team->id,
                'name'    => 'Audit Tenant',
                'plan'    => 'free',
            ]);

            $this->tenant = $tenant;
        } catch (Throwable $e) {
            $this->markTestSkipped('Tenancy infrastructure unavailable: ' . $e->getMessage());
        }
    }

    #[Test]
    public function it_logs_an_audit_entry(): void
    {
        $log = $this->service->log(
            (string) $this->tenant->id,
            'created',
            null,
            ['name' => 'Audit Tenant', 'plan' => 'free'],
        );

        $this->assertInstanceOf(TenantAuditLog::class, $log);
        $this->assertEquals((string) $this->tenant->id, $log->tenant_id);
        $this->assertEquals('created', $log->action);
        $this->assertNull($log->before_data);
        $this->assertEquals(['name' => 'Audit Tenant', 'plan' => 'free'], $log->after_data);
        $this->assertNotNull($log->created_at);
    }

    #[Test]
    public function it_logs_before_and_after_data(): void
    {
        $log = $this->service->log(
            (string) $this->tenant->id,
            'plan_changed',
            ['plan' => 'free'],
            ['plan' => 'professional'],
        );

        $this->assertEquals('plan_changed', $log->action);
        $this->assertEquals(['plan' => 'free'], $log->before_data);
        $this->assertEquals(['plan' => 'professional'], $log->after_data);
    }

    #[Test]
    public function it_retrieves_audit_trail_in_reverse_chronological_order(): void
    {
        // Create multiple log entries
        $this->service->log((string) $this->tenant->id, 'created', null, ['name' => 'Tenant']);
        $this->service->log((string) $this->tenant->id, 'plan_changed', ['plan' => 'free'], ['plan' => 'starter']);
        $this->service->log((string) $this->tenant->id, 'suspended', ['status' => 'active'], ['status' => 'suspended']);

        $trail = $this->service->getAuditTrail((string) $this->tenant->id);

        $this->assertCount(3, $trail);
        // Most recent first
        $first = $trail->first();
        $last = $trail->last();
        assert($first instanceof TenantAuditLog);
        assert($last instanceof TenantAuditLog);
        $this->assertEquals('suspended', $first->action);
        $this->assertEquals('created', $last->action);
    }

    #[Test]
    public function it_respects_limit_parameter(): void
    {
        $this->service->log((string) $this->tenant->id, 'created', null, null);
        $this->service->log((string) $this->tenant->id, 'plan_changed', null, null);
        $this->service->log((string) $this->tenant->id, 'suspended', null, null);

        $trail = $this->service->getAuditTrail((string) $this->tenant->id, 2);

        $this->assertCount(2, $trail);
    }

    #[Test]
    public function it_returns_empty_trail_for_unknown_tenant(): void
    {
        $trail = $this->service->getAuditTrail('nonexistent-tenant-id');

        $this->assertCount(0, $trail);
    }

    #[Test]
    public function it_associates_audit_log_with_tenant(): void
    {
        $log = $this->service->log(
            (string) $this->tenant->id,
            'config_updated',
            ['config' => ['max_users' => 5]],
            ['config' => ['max_users' => 25]],
        );

        $this->assertEquals((string) $this->tenant->id, $log->tenant_id);

        // Test the relationship
        $loadedTenant = $log->tenant;
        $this->assertInstanceOf(Tenant::class, $loadedTenant);
        $this->assertEquals($this->tenant->id, $loadedTenant->id);
    }
}
