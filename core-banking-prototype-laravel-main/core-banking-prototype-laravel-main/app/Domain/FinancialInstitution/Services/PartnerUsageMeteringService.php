<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerUsageRecord;
use Carbon\Carbon;

/**
 * Tracks and manages partner API usage metering for billing and analytics.
 */
class PartnerUsageMeteringService
{
    public function __construct(
        private readonly PartnerTierService $tierService,
    ) {
    }

    /**
     * Record an API call for a partner.
     */
    public function recordApiCall(
        FinancialInstitutionPartner $partner,
        string $endpoint,
        bool $success = true,
        ?int $responseTimeMs = null,
    ): void {
        $record = $this->getOrCreateTodayRecord($partner);

        $record->incrementApiCalls(1, $success, $endpoint);

        if ($responseTimeMs !== null) {
            $this->updateResponseTime($record, $responseTimeMs);
        }
    }

    /**
     * Record a widget load event.
     */
    public function recordWidgetLoad(
        FinancialInstitutionPartner $partner,
        string $widgetType,
        bool $converted = false,
    ): void {
        $record = $this->getOrCreateTodayRecord($partner);

        $record->increment('widget_loads');

        if ($converted) {
            $record->increment('widget_conversions');
        }
    }

    /**
     * Record an SDK download event.
     */
    public function recordSdkDownload(
        FinancialInstitutionPartner $partner,
        string $language,
    ): void {
        $record = $this->getOrCreateTodayRecord($partner);

        $record->increment('sdk_downloads');
    }

    /**
     * Get or create the daily usage record for today.
     */
    public function getOrCreateTodayRecord(FinancialInstitutionPartner $partner): PartnerUsageRecord
    {
        $today = now()->startOfDay();

        $record = PartnerUsageRecord::where('partner_id', $partner->id)
            ->whereDate('usage_date', $today)
            ->where('period_type', 'daily')
            ->first();

        if ($record) {
            return $record;
        }

        return PartnerUsageRecord::create([
            'uuid'               => (string) \Illuminate\Support\Str::uuid(),
            'partner_id'         => $partner->id,
            'usage_date'         => $today->toDateString(),
            'period_type'        => 'daily',
            'api_calls'          => 0,
            'api_calls_success'  => 0,
            'api_calls_failed'   => 0,
            'widget_loads'       => 0,
            'widget_conversions' => 0,
            'sdk_downloads'      => 0,
            'is_billable'        => true,
        ]);
    }

    /**
     * Get aggregated usage summary for a date range.
     *
     * @return array<string, mixed>
     */
    public function getUsageSummary(
        FinancialInstitutionPartner $partner,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $records = PartnerUsageRecord::where('partner_id', $partner->id)
            ->whereBetween('usage_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $tier = $this->tierService->getPartnerTier($partner);

        $totalCalls = (int) $records->sum('api_calls');
        $totalSuccess = (int) $records->sum('api_calls_success');
        $totalFailed = (int) $records->sum('api_calls_failed');

        return [
            'partner_id'   => $partner->id,
            'period_start' => $startDate->toDateString(),
            'period_end'   => $endDate->toDateString(),
            'tier'         => $tier->value,
            'api_calls'    => [
                'total'        => $totalCalls,
                'successful'   => $totalSuccess,
                'failed'       => $totalFailed,
                'success_rate' => $totalCalls > 0
                    ? round(($totalSuccess / $totalCalls) * 100, 2)
                    : 0.0,
            ],
            'widget_loads'       => (int) $records->sum('widget_loads'),
            'widget_conversions' => (int) $records->sum('widget_conversions'),
            'sdk_downloads'      => (int) $records->sum('sdk_downloads'),
            'daily_records'      => $records->count(),
        ];
    }

    /**
     * Check whether the partner has exceeded their monthly usage limit.
     *
     * @return array{exceeded: bool, current: int, limit: int, percentage: float}
     */
    public function checkUsageLimit(FinancialInstitutionPartner $partner): array
    {
        $tier = $this->tierService->getPartnerTier($partner);
        $limit = $tier->apiCallLimit();

        $currentUsage = (int) PartnerUsageRecord::where('partner_id', $partner->id)
            ->whereYear('usage_date', now()->year)
            ->whereMonth('usage_date', now()->month)
            ->sum('api_calls');

        return [
            'exceeded'   => $currentUsage >= $limit,
            'current'    => $currentUsage,
            'limit'      => $limit,
            'percentage' => $limit > 0
                ? round(($currentUsage / $limit) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * Update rolling average and p99 response times.
     */
    private function updateResponseTime(PartnerUsageRecord $record, int $responseTimeMs): void
    {
        $currentAvg = (float) ($record->avg_response_time_ms ?? 0);
        $currentP99 = (int) ($record->p99_response_time_ms ?? 0);
        $totalCalls = (int) $record->api_calls;

        // Simple rolling average
        $newAvg = $totalCalls > 1
            ? (($currentAvg * ($totalCalls - 1)) + $responseTimeMs) / $totalCalls
            : (float) $responseTimeMs;

        $updates = ['avg_response_time_ms' => round($newAvg, 2)];

        if ($responseTimeMs > $currentP99) {
            $updates['p99_response_time_ms'] = $responseTimeMs;
        }

        $record->update($updates);
    }
}
