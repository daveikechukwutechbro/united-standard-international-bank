<?php

declare(strict_types=1);

namespace App\Services\MultiTenancy;

use App\Models\TenantAuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Service for recording and retrieving tenant audit trail entries.
 *
 * Captures all tenant lifecycle events including creation, suspension,
 * reactivation, deletion, plan changes, and configuration updates.
 */
class TenantAuditService
{
    /**
     * Record an audit log entry for a tenant action.
     *
     * @param array<string, mixed>|null $beforeData State before the action
     * @param array<string, mixed>|null $afterData  State after the action
     */
    public function log(
        string $tenantId,
        string $action,
        ?array $beforeData = null,
        ?array $afterData = null,
    ): TenantAuditLog {
        return TenantAuditLog::create([
            'tenant_id'   => $tenantId,
            'user_id'     => Auth::id(),
            'action'      => $action,
            'before_data' => $beforeData,
            'after_data'  => $afterData,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
            'created_at'  => now(),
        ]);
    }

    /**
     * Get the audit trail for a specific tenant.
     *
     * @return Collection<int, TenantAuditLog>
     */
    public function getAuditTrail(string $tenantId, int $limit = 50): Collection
    {
        return TenantAuditLog::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
