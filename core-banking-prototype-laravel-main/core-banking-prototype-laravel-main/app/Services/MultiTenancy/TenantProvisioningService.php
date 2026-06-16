<?php

declare(strict_types=1);

namespace App\Services\MultiTenancy;

use App\Events\Tenant\TenantCreated;
use App\Events\Tenant\TenantDeleted;
use App\Events\Tenant\TenantSuspended;
use App\Models\Team;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Service for provisioning, configuring, and managing tenant lifecycle.
 *
 * Handles:
 * - Creating new tenants from teams
 * - Configuring tenant plans and settings
 * - Running tenant-specific migrations
 * - Suspending and deleting tenants
 * - Soft-delete with 14-day grace period
 * - Audit logging of all lifecycle events
 */
class TenantProvisioningService
{
    /**
     * Available tenant plans with their default configurations.
     *
     * @var array<string, array{max_users: int, max_api_calls: int, max_storage_mb: int, features: array<string>}>
     */
    protected array $planDefaults = [
        'free' => [
            'max_users'      => 5,
            'max_api_calls'  => 1000,
            'max_storage_mb' => 100,
            'features'       => ['basic_banking', 'reports'],
        ],
        'starter' => [
            'max_users'      => 25,
            'max_api_calls'  => 10000,
            'max_storage_mb' => 1000,
            'features'       => ['basic_banking', 'reports', 'api_access', 'webhooks'],
        ],
        'professional' => [
            'max_users'      => 100,
            'max_api_calls'  => 100000,
            'max_storage_mb' => 10000,
            'features'       => ['basic_banking', 'reports', 'api_access', 'webhooks', 'multi_currency', 'audit_logs'],
        ],
        'enterprise' => [
            'max_users'      => -1, // unlimited
            'max_api_calls'  => -1,
            'max_storage_mb' => -1,
            'features'       => ['basic_banking', 'reports', 'api_access', 'webhooks', 'multi_currency', 'audit_logs', 'custom_integrations', 'dedicated_support'],
        ],
    ];

    /** @var int Grace period in days before a scheduled deletion is purged */
    public const DELETION_GRACE_DAYS = 14;

    public function __construct(
        private readonly ?TenantAuditService $auditService = null,
    ) {
    }

    /**
     * Create a new tenant for the given team.
     *
     * @param array<string, mixed> $config Additional tenant configuration
     *
     * @throws RuntimeException If tenant already exists for the team
     */
    public function createTenant(Team $team, string $name, string $plan = 'free', array $config = []): Tenant
    {
        // Validate plan
        if (! isset($this->planDefaults[$plan])) {
            throw new RuntimeException("Invalid plan: {$plan}. Available plans: " . implode(', ', array_keys($this->planDefaults)));
        }

        // Check if tenant already exists for this team
        $existing = Tenant::where('team_id', $team->id)->first();
        if ($existing instanceof Tenant) {
            throw new RuntimeException("Tenant already exists for team {$team->id}: {$existing->id}");
        }

        return DB::transaction(function () use ($team, $name, $plan, $config): Tenant {
            $planConfig = $this->planDefaults[$plan];

            /** @var Tenant $tenant */
            $tenant = Tenant::create([
                'team_id' => $team->id,
                'name'    => $name,
                'plan'    => $plan,
                'data'    => array_merge([
                    'config'     => array_merge($planConfig, $config),
                    'status'     => 'active',
                    'created_by' => $team->user_id,
                ]),
            ]);

            Log::info('Tenant created', [
                'tenant_id' => $tenant->id,
                'team_id'   => $team->id,
                'plan'      => $plan,
            ]);

            $this->audit((string) $tenant->id, 'created', null, [
                'name'    => $name,
                'plan'    => $plan,
                'team_id' => $team->id,
            ]);

            event(new TenantCreated($tenant, $plan));

            return $tenant;
        });
    }

    /**
     * Set the plan for a tenant.
     *
     * @throws RuntimeException If the plan is invalid
     */
    public function setTenantPlan(Tenant $tenant, string $plan): Tenant
    {
        if (! isset($this->planDefaults[$plan])) {
            throw new RuntimeException("Invalid plan: {$plan}. Available plans: " . implode(', ', array_keys($this->planDefaults)));
        }

        return DB::transaction(function () use ($tenant, $plan): Tenant {
            $oldPlan = $tenant->plan;
            $planConfig = $this->planDefaults[$plan];

            $tenant->plan = $plan;

            // Merge plan config into existing data, preserving custom overrides
            /** @var array<string, mixed> $existingData */
            $existingData = $tenant->data ?? [];
            /** @var array<string, mixed> $existingConfig */
            $existingConfig = $existingData['config'] ?? [];

            $existingData['config'] = array_merge($existingConfig, $planConfig);
            $tenant->data = $existingData;

            $tenant->save();

            Log::info('Tenant plan updated', [
                'tenant_id' => $tenant->id,
                'old_plan'  => $oldPlan,
                'new_plan'  => $plan,
            ]);

            $this->audit((string) $tenant->id, 'plan_changed', [
                'plan' => $oldPlan,
            ], [
                'plan' => $plan,
            ]);

            return $tenant;
        });
    }

    /**
     * Update tenant configuration.
     *
     * @param array<string, mixed> $config Configuration key-value pairs to update
     */
    public function updateTenantConfig(Tenant $tenant, array $config): Tenant
    {
        return DB::transaction(function () use ($tenant, $config): Tenant {
            /** @var array<string, mixed> $existingData */
            $existingData = $tenant->data ?? [];
            /** @var array<string, mixed> $existingConfig */
            $existingConfig = $existingData['config'] ?? [];

            $beforeConfig = $existingConfig;

            $existingData['config'] = array_merge($existingConfig, $config);
            $tenant->data = $existingData;

            $tenant->save();

            Log::info('Tenant config updated', [
                'tenant_id' => $tenant->id,
                'keys'      => array_keys($config),
            ]);

            $this->audit((string) $tenant->id, 'config_updated', [
                'config' => $beforeConfig,
            ], [
                'config' => $existingData['config'],
            ]);

            return $tenant;
        });
    }

    /**
     * Run tenant-specific database migrations.
     *
     * @return array{status: string, output: string}
     */
    public function runTenantMigrations(Tenant $tenant): array
    {
        Log::info('Running tenant migrations', ['tenant_id' => $tenant->id]);

        try {
            tenancy()->initialize($tenant);

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
            ]);

            $output = Artisan::output();

            Log::info('Tenant migrations completed', [
                'tenant_id' => $tenant->id,
            ]);

            return [
                'status' => 'success',
                'output' => $output,
            ];
        } catch (Exception $e) {
            Log::error('Tenant migrations failed', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'output' => $e->getMessage(),
            ];
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Suspend a tenant, preventing access to the platform.
     */
    public function suspendTenant(Tenant $tenant, string $reason = 'Administrative action'): Tenant
    {
        return DB::transaction(function () use ($tenant, $reason): Tenant {
            /** @var array<string, mixed> $data */
            $data = $tenant->data ?? [];
            $beforeStatus = $data['status'] ?? 'active';
            $data['status'] = 'suspended';
            $data['suspended_at'] = now()->toIso8601String();
            $data['suspension_reason'] = $reason;
            $tenant->data = $data;

            $tenant->save();

            Log::warning('Tenant suspended', [
                'tenant_id' => $tenant->id,
                'reason'    => $reason,
            ]);

            $this->audit((string) $tenant->id, 'suspended', [
                'status' => $beforeStatus,
            ], [
                'status' => 'suspended',
                'reason' => $reason,
            ]);

            event(new TenantSuspended($tenant, $reason));

            return $tenant;
        });
    }

    /**
     * Reactivate a previously suspended tenant.
     */
    public function reactivateTenant(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant): Tenant {
            /** @var array<string, mixed> $data */
            $data = $tenant->data ?? [];
            $beforeStatus = $data['status'] ?? 'suspended';
            unset($data['suspended_at'], $data['suspension_reason']);
            $data['status'] = 'active';
            $tenant->data = $data;

            $tenant->save();

            Log::info('Tenant reactivated', [
                'tenant_id' => $tenant->id,
            ]);

            $this->audit((string) $tenant->id, 'reactivated', [
                'status' => $beforeStatus,
            ], [
                'status' => 'active',
            ]);

            return $tenant;
        });
    }

    /**
     * Schedule a tenant for deletion with a 14-day grace period.
     *
     * Sets deletion_scheduled_at and suspends the tenant. The tenant
     * can be restored within the grace period via restoreTenant().
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        $tenantId = (string) $tenant->id;
        $tenantName = $tenant->name;

        return DB::transaction(function () use ($tenant, $tenantId, $tenantName): bool {
            $scheduledAt = now()->addDays(self::DELETION_GRACE_DAYS);

            $tenant->deletion_scheduled_at = $scheduledAt;

            /** @var array<string, mixed> $data */
            $data = $tenant->data ?? [];
            $data['status'] = 'pending_deletion';
            $tenant->data = $data;

            $tenant->save();

            Log::warning('Tenant scheduled for deletion', [
                'tenant_id'             => $tenantId,
                'tenant_name'           => $tenantName,
                'deletion_scheduled_at' => $scheduledAt->toIso8601String(),
            ]);

            $this->audit($tenantId, 'deleted', [
                'name' => $tenantName,
            ], [
                'deletion_scheduled_at' => $scheduledAt->toIso8601String(),
                'grace_days'            => self::DELETION_GRACE_DAYS,
            ]);

            event(new TenantDeleted($tenantId, $tenantName));

            return true;
        });
    }

    /**
     * Restore a tenant that was scheduled for deletion.
     *
     * Clears the deletion schedule and reactivates the tenant.
     *
     * @throws RuntimeException If the tenant is not scheduled for deletion
     */
    public function restoreTenant(string $tenantId): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::withTrashed()->find($tenantId);

        if ($tenant === null) {
            throw new RuntimeException("Tenant not found: {$tenantId}");
        }

        return DB::transaction(function () use ($tenant): Tenant {
            // Restore soft-delete if applicable
            if ($tenant->trashed()) {
                $tenant->restore();
            }

            $tenant->deletion_scheduled_at = null;

            /** @var array<string, mixed> $data */
            $data = $tenant->data ?? [];
            $data['status'] = 'active';
            unset($data['suspended_at'], $data['suspension_reason']);
            $tenant->data = $data;

            $tenant->save();

            Log::info('Tenant restored from scheduled deletion', [
                'tenant_id' => $tenant->id,
            ]);

            $this->audit((string) $tenant->id, 'reactivated', [
                'status' => 'pending_deletion',
            ], [
                'status'   => 'active',
                'restored' => true,
            ]);

            return $tenant;
        });
    }

    /**
     * Permanently purge a tenant that has passed its grace period.
     *
     * Only allows purging if deletion_scheduled_at is in the past.
     *
     * @throws RuntimeException If the tenant is not eligible for purging
     */
    public function purgeTenant(string $tenantId): bool
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::withTrashed()->find($tenantId);

        if ($tenant === null) {
            throw new RuntimeException("Tenant not found: {$tenantId}");
        }

        if ($tenant->deletion_scheduled_at === null) {
            throw new RuntimeException("Tenant {$tenantId} is not scheduled for deletion");
        }

        $scheduledAt = Carbon::parse($tenant->deletion_scheduled_at);
        if ($scheduledAt->isFuture()) {
            throw new RuntimeException(
                "Tenant {$tenantId} grace period has not expired. Scheduled for: {$scheduledAt->toIso8601String()}"
            );
        }

        $tenantName = $tenant->name;

        return DB::transaction(function () use ($tenant, $tenantId, $tenantName): bool {
            Log::warning('Permanently purging tenant', [
                'tenant_id'   => $tenantId,
                'tenant_name' => $tenantName,
            ]);

            $this->audit($tenantId, 'purged', [
                'name' => $tenantName,
            ], null);

            // Force-delete the tenant (bypass soft-delete)
            $tenant->forceDelete();

            Log::info('Tenant permanently purged', [
                'tenant_id'   => $tenantId,
                'tenant_name' => $tenantName,
            ]);

            return true;
        });
    }

    /**
     * Get the status of a tenant.
     */
    public function getTenantStatus(Tenant $tenant): string
    {
        /** @var array<string, mixed> $data */
        $data = $tenant->data ?? [];

        return (string) ($data['status'] ?? 'active');
    }

    /**
     * Check if a tenant is active and not suspended.
     */
    public function isTenantActive(Tenant $tenant): bool
    {
        return $this->getTenantStatus($tenant) === 'active';
    }

    /**
     * Get the configuration for a tenant.
     *
     * @return array<string, mixed>
     */
    public function getTenantConfig(Tenant $tenant): array
    {
        /** @var array<string, mixed> $data */
        $data = $tenant->data ?? [];

        /** @var array<string, mixed> $config */
        $config = $data['config'] ?? [];

        return $config;
    }

    /**
     * Get the available plans and their configurations.
     *
     * @return array<string, array{max_users: int, max_api_calls: int, max_storage_mb: int, features: array<string>}>
     */
    public function getAvailablePlans(): array
    {
        return $this->planDefaults;
    }

    /**
     * Record an audit log entry if the audit service is available.
     *
     * @param array<string, mixed>|null $beforeData
     * @param array<string, mixed>|null $afterData
     */
    private function audit(string $tenantId, string $action, ?array $beforeData, ?array $afterData): void
    {
        $this->auditService?->log($tenantId, $action, $beforeData, $afterData);
    }
}
