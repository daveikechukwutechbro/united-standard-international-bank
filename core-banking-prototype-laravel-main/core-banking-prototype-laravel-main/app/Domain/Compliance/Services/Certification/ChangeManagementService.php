<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\ComplianceChangeLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SOC 2 Change Management Service.
 *
 * Records and tracks all system changes (deployments, configuration updates,
 * access modifications, infrastructure changes) for SOC 2 audit trail
 * requirements (CC8.1 Change Management).
 */
class ChangeManagementService
{
    /**
     * Log a change management entry.
     *
     * @param string      $type            Change type (e.g. 'deployment', 'configuration', 'access_change', 'infrastructure')
     * @param string      $description     Human-readable description of the change
     * @param array<string, mixed>|null $oldValues Previous state values
     * @param array<string, mixed>|null $newValues New state values
     * @param string|null $changedBy       Identifier of the person or system that made the change
     * @param string|null $ticketReference Reference to a ticket/issue tracking the change
     */
    public function logChange(
        string $type,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $changedBy = null,
        ?string $ticketReference = null
    ): ComplianceChangeLog {
        $changeLog = ComplianceChangeLog::create([
            'change_type'      => $type,
            'description'      => $description,
            'old_values'       => $oldValues,
            'new_values'       => $newValues,
            'changed_by'       => $changedBy ?? auth()->user()?->name ?? 'system',
            'environment'      => app()->environment(),
            'ticket_reference' => $ticketReference,
            'metadata'         => [
                'recorded_at' => now()->toIso8601String(),
                'source'      => static::class,
            ],
        ]);

        Log::info('SOC 2 change logged', [
            'id'          => $changeLog->id,
            'type'        => $type,
            'description' => $description,
            'changed_by'  => $changeLog->changed_by,
        ]);

        return $changeLog;
    }

    /**
     * Retrieve change log entries with optional filters.
     *
     * @param string|null $period Filter by audit period (e.g. '2026-Q1', '2026-01')
     * @param string|null $type   Filter by change type
     *
     * @return Collection<int, ComplianceChangeLog>
     */
    public function getChanges(?string $period = null, ?string $type = null): Collection
    {
        $query = ComplianceChangeLog::query();

        if ($period !== null) {
            [$startDate, $endDate] = $this->parsePeriodDates($period);
            $query->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate);
        }

        if ($type !== null) {
            $query->forType($type);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Generate a change management summary report for a period.
     *
     * @param string $period The audit period identifier
     *
     * @return array<string, mixed>
     */
    public function generateChangeReport(string $period): array
    {
        $changes = $this->getChanges($period);

        $byType = $changes->groupBy('change_type')->map(function (Collection $typeChanges, string $type) {
            return [
                'type'           => $type,
                'count'          => $typeChanges->count(),
                'with_ticket'    => $typeChanges->filter(fn (ComplianceChangeLog $c) => $c->ticket_reference !== null)->count(),
                'without_ticket' => $typeChanges->filter(fn (ComplianceChangeLog $c) => $c->ticket_reference === null)->count(),
                'changers'       => $typeChanges->unique('changed_by')->pluck('changed_by')->filter()->values()->toArray(),
            ];
        })->toArray();

        $byEnvironment = $changes->groupBy('environment')->map->count()->toArray();

        $ticketCoverage = $changes->count() > 0
            ? round(($changes->filter(fn (ComplianceChangeLog $c) => $c->ticket_reference !== null)->count() / $changes->count()) * 100, 2)
            : 0.0;

        return [
            'period'       => $period,
            'generated_at' => now()->toIso8601String(),
            'summary'      => [
                'total_changes'           => $changes->count(),
                'change_types'            => array_keys($byType),
                'ticket_coverage_percent' => $ticketCoverage,
                'unique_changers'         => $changes->unique('changed_by')->count(),
            ],
            'by_type'        => $byType,
            'by_environment' => $byEnvironment,
            'timeline'       => $changes->map(function (ComplianceChangeLog $change) {
                return [
                    'id'               => $change->id,
                    'type'             => $change->change_type,
                    'description'      => $change->description,
                    'changed_by'       => $change->changed_by,
                    'environment'      => $change->environment,
                    'ticket_reference' => $change->ticket_reference,
                    'created_at'       => $change->created_at?->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get the most recent deployment-type changes.
     *
     * @param int $limit Maximum number of results
     *
     * @return Collection<int, ComplianceChangeLog>
     */
    public function getRecentDeployments(int $limit = 10): Collection
    {
        return ComplianceChangeLog::forType('deployment')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Parse a period string into start and end Carbon dates.
     *
     * @param string $period Period identifier ('YYYY-QN' or 'YYYY-MM')
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parsePeriodDates(string $period): array
    {
        if (preg_match('/^(\d{4})-Q(\d)$/', $period, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
            $startMonth = ($quarter - 1) * 3 + 1;

            $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
            $endDate = $startDate->copy()->addMonths(3)->subSecond();

            return [$startDate, $endDate];
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
            $startDate = Carbon::create((int) $matches[1], (int) $matches[2], 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth();

            return [$startDate, $endDate];
        }

        $reviewDays = (int) config('compliance-certification.soc2.review_period', 90);
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($reviewDays);

        return [$startDate, $endDate];
    }
}
