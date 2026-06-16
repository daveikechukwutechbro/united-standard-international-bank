<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\DataTransferLog;
use App\Domain\Compliance\Models\TenantRegionMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DataResidencyService
{
    /**
     * Get the region for a given tenant.
     */
    public function getRegionForTenant(string $tenantId): string
    {
        $mapping = TenantRegionMapping::forTenant($tenantId)
            ->primary()
            ->active()
            ->first();

        if ($mapping) {
            return $mapping->region;
        }

        return Config::get('compliance-certification.data_residency.default_region', 'EU');
    }

    /**
     * Set the region for a tenant.
     */
    public function setTenantRegion(string $tenantId, string $region, bool $isPrimary = true): TenantRegionMapping
    {
        if ($isPrimary) {
            TenantRegionMapping::forTenant($tenantId)
                ->primary()
                ->update(['is_primary' => false]);
        }

        return TenantRegionMapping::updateOrCreate(
            ['tenant_id' => $tenantId, 'region' => $region],
            [
                'is_primary'     => $isPrimary,
                'effective_from' => now(),
            ],
        );
    }

    /**
     * Get all region mappings for a tenant.
     *
     * @return Collection<int, TenantRegionMapping>
     */
    public function getTenantRegions(string $tenantId): Collection
    {
        return TenantRegionMapping::forTenant($tenantId)->active()->get();
    }

    /**
     * Validate a cross-region data transfer.
     *
     * @return array<string, mixed>
     */
    public function validateTransfer(string $fromRegion, string $toRegion, string $dataType): array
    {
        $config = Config::get('compliance-certification.data_residency.cross_region_transfer', []);
        $requireApproval = $config['require_approval'] ?? true;

        $allowedPairs = $config['allowed_pairs'] ?? [];
        $isAllowed = false;

        foreach ($allowedPairs as $pair) {
            if (
                ($pair[0] === $fromRegion && $pair[1] === $toRegion)
                || ($pair[0] === $toRegion && $pair[1] === $fromRegion)
            ) {
                $isAllowed = true;
                break;
            }
        }

        return [
            'from_region'       => $fromRegion,
            'to_region'         => $toRegion,
            'data_type'         => $dataType,
            'allowed'           => $isAllowed,
            'requires_approval' => $requireApproval,
            'is_cross_region'   => $fromRegion !== $toRegion,
        ];
    }

    /**
     * Log a data transfer event.
     */
    public function logTransfer(
        string $fromRegion,
        string $toRegion,
        string $dataType,
        ?string $reason = null,
        ?string $approvedBy = null,
        ?string $userUuid = null,
    ): DataTransferLog {
        $validation = $this->validateTransfer($fromRegion, $toRegion, $dataType);

        $status = 'logged';
        if ($validation['requires_approval'] && ! $approvedBy) {
            $status = 'pending_approval';
        } elseif (! $validation['allowed']) {
            $status = 'denied';
        }

        $log = DataTransferLog::create([
            'from_region' => $fromRegion,
            'to_region'   => $toRegion,
            'data_type'   => $dataType,
            'reason'      => $reason,
            'approved_by' => $approvedBy,
            'status'      => $status,
            'user_uuid'   => $userUuid,
            'metadata'    => $validation,
        ]);

        Log::info('Data transfer logged', [
            'from'      => $fromRegion,
            'to'        => $toRegion,
            'data_type' => $dataType,
            'status'    => $status,
        ]);

        return $log;
    }

    /**
     * Get transfer logs with optional filters.
     *
     * @return Collection<int, DataTransferLog>
     */
    public function getTransferLogs(?string $fromRegion = null, ?string $toRegion = null): Collection
    {
        $query = DataTransferLog::query();

        if ($fromRegion) {
            $query->fromRegion($fromRegion);
        }
        if ($toRegion) {
            $query->toRegion($toRegion);
        }

        return $query->orderByDesc('created_at')->limit(100)->get();
    }

    /**
     * Get data residency status overview.
     *
     * @return array<string, mixed>
     */
    public function getResidencyStatus(): array
    {
        $enabled = Config::get('compliance-certification.data_residency.enabled', false);
        $regions = Config::get('compliance-certification.data_residency.regions', []);
        $defaultRegion = Config::get('compliance-certification.data_residency.default_region', 'EU');

        $tenantMappings = TenantRegionMapping::active()->get();
        $transferCount = DataTransferLog::where('created_at', '>=', now()->subDays(30))->count();
        $crossRegionCount = DataTransferLog::crossRegion()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'enabled'                         => $enabled,
            'default_region'                  => $defaultRegion,
            'available_regions'               => array_keys($regions),
            'tenant_mappings'                 => $tenantMappings->count(),
            'region_distribution'             => $tenantMappings->groupBy('region')->map->count()->toArray(),
            'transfers_last_30d'              => $transferCount,
            'cross_region_transfers_last_30d' => $crossRegionCount,
            'generated_at'                    => now()->toIso8601String(),
        ];
    }

    /**
     * Get demo residency status.
     *
     * @return array<string, mixed>
     */
    public function getDemoStatus(): array
    {
        return [
            'enabled'             => true,
            'default_region'      => 'EU',
            'available_regions'   => ['EU', 'US', 'APAC', 'UK'],
            'tenant_mappings'     => 12,
            'region_distribution' => [
                'EU'   => 5,
                'US'   => 4,
                'APAC' => 2,
                'UK'   => 1,
            ],
            'transfers_last_30d'              => 847,
            'cross_region_transfers_last_30d' => 23,
            'generated_at'                    => now()->toIso8601String(),
        ];
    }
}
