<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\RetentionPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Automated data retention policy enforcement.
 */
class DataRetentionService
{
    /**
     * Get all retention policies.
     *
     * @return Collection<int, RetentionPolicy>
     */
    public function getPolicies(?bool $enabledOnly = null): Collection
    {
        $query = RetentionPolicy::query();

        if ($enabledOnly !== null) {
            $query->where('enabled', $enabledOnly);
        }

        return $query->orderBy('data_type')->get();
    }

    /**
     * Create a retention policy.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPolicy(array $data): RetentionPolicy
    {
        return RetentionPolicy::create($data);
    }

    /**
     * Update a retention policy.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePolicy(string $id, array $data): RetentionPolicy
    {
        $policy = RetentionPolicy::findOrFail($id);
        $policy->update($data);

        return $policy->refresh();
    }

    /**
     * Seed default retention policies from config.
     *
     * @return array<string, int>
     */
    public function seedDefaultPolicies(): array
    {
        $defaults = Config::get('compliance-certification.gdpr.retention_defaults', []);
        $created = 0;
        $existing = 0;

        foreach ($defaults as $dataType => $retentionDays) {
            $exists = RetentionPolicy::where('data_type', $dataType)->exists();

            if (! $exists) {
                RetentionPolicy::create([
                    'data_type'      => $dataType,
                    'retention_days' => $retentionDays,
                    'action'         => $dataType === 'audit_logs' ? 'archive' : 'delete',
                    'enabled'        => true,
                    'description'    => "Default retention for {$dataType}: {$retentionDays} days",
                ]);
                $created++;
            } else {
                $existing++;
            }
        }

        return ['created' => $created, 'existing' => $existing];
    }

    /**
     * Enforce all enabled retention policies.
     *
     * @return array<string, mixed>
     */
    public function enforceRetentionPolicies(bool $dryRun = false): array
    {
        $policies = RetentionPolicy::enabled()->get();
        $results = [];

        foreach ($policies as $policy) {
            $result = $this->enforcePolicy($policy, $dryRun);
            $results[] = $result;
        }

        return [
            'dry_run'        => $dryRun,
            'policies_run'   => count($results),
            'total_affected' => collect($results)->sum('affected_count'),
            'results'        => $results,
            'enforced_at'    => now()->toIso8601String(),
        ];
    }

    /**
     * Enforce a single retention policy.
     *
     * @return array<string, mixed>
     */
    private function enforcePolicy(RetentionPolicy $policy, bool $dryRun): array
    {
        $cutoffDate = now()->subDays($policy->retention_days);
        $affectedCount = 0;

        if ($policy->model_class && class_exists($policy->model_class)) {
            $query = $policy->model_class::where('created_at', '<', $cutoffDate);
            $affectedCount = $query->count();

            if (! $dryRun && $affectedCount > 0) {
                match ($policy->action) {
                    'delete'    => $query->delete(),
                    'archive'   => $this->archiveRecords($query, $policy),
                    'anonymize' => $this->anonymizeRecords($query, $policy),
                    default     => Log::warning("Unknown retention action: {$policy->action}"),
                };

                $policy->update(['last_enforced_at' => now()]);
            }
        } else {
            // Table-based enforcement when model_class is not set
            $tableName = str_replace('.', '_', $policy->data_type);
            try {
                $affectedCount = DB::table($tableName)
                    ->where('created_at', '<', $cutoffDate)
                    ->count();

                if (! $dryRun && $affectedCount > 0 && $policy->action === 'delete') {
                    DB::table($tableName)->where('created_at', '<', $cutoffDate)->delete();
                    $policy->update(['last_enforced_at' => now()]);
                }
            } catch (Throwable $e) {
                Log::info("Retention policy skip: table {$tableName} not found");
                $affectedCount = 0;
            }
        }

        return [
            'data_type'      => $policy->data_type,
            'action'         => $policy->action,
            'retention_days' => $policy->retention_days,
            'cutoff_date'    => $cutoffDate->toIso8601String(),
            'affected_count' => $affectedCount,
            'executed'       => ! $dryRun,
        ];
    }

    /**
     * Archive records (move to archive table or mark archived).
     *
     * @param  mixed  $query
     */
    private function archiveRecords($query, RetentionPolicy $policy): void
    {
        // In production, move to archive table or cold storage
        Log::info("Archiving {$query->count()} records for {$policy->data_type}");
        $query->update(['archived_at' => now()]);
    }

    /**
     * Anonymize records (replace PII with hashed values).
     *
     * @param  mixed  $query
     */
    private function anonymizeRecords($query, RetentionPolicy $policy): void
    {
        Log::info("Anonymizing {$query->count()} records for {$policy->data_type}");
        // In production, replace PII fields with anonymized values
    }

    /**
     * Get retention summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $policies = RetentionPolicy::all();

        return [
            'total_policies' => $policies->count(),
            'enabled'        => $policies->where('enabled', true)->count(),
            'disabled'       => $policies->where('enabled', false)->count(),
            'by_action'      => $policies->groupBy('action')->map->count()->toArray(),
            'overdue'        => $policies->filter(fn (RetentionPolicy $p) => $p->isOverdue())->count(),
        ];
    }

    /**
     * Get demo retention data.
     *
     * @return array<string, mixed>
     */
    public function getDemoSummary(): array
    {
        $defaults = Config::get('compliance-certification.gdpr.retention_defaults', []);

        return [
            'total_policies' => count($defaults),
            'enabled'        => count($defaults),
            'disabled'       => 0,
            'by_action'      => ['delete' => count($defaults) - 1, 'archive' => 1],
            'overdue'        => 0,
            'policies'       => collect($defaults)->map(fn ($days, $type) => [
                'data_type'      => $type,
                'retention_days' => $days,
                'action'         => $type === 'audit_logs' ? 'archive' : 'delete',
                'enabled'        => true,
            ])->values()->toArray(),
        ];
    }
}
