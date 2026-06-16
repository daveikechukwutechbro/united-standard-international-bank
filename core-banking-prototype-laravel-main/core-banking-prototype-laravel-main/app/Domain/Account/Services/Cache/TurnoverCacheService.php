<?php

declare(strict_types=1);

namespace App\Domain\Account\Services\Cache;

use App\Domain\Account\Models\Turnover;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TurnoverCacheService
{
    /**
     * Cache key prefix for turnovers.
     */
    private const CACHE_PREFIX = 'turnover:';

    /**
     * Cache duration in seconds (2 hours for turnovers as they change less frequently).
     */
    private const CACHE_TTL = 7200;

    /**
     * Get latest turnover for account.
     */
    public function getLatest(string $accountUuid): ?Turnover
    {
        $accountUuid = (string) $accountUuid;

        return Cache::remember(
            $this->getCacheKey($accountUuid, 'latest'),
            self::CACHE_TTL,
            fn () => Turnover::where('account_uuid', $accountUuid)
                ->orderBy('created_at', 'desc')
                ->first()
        );
    }

    /**
     * Get turnovers for last N months.
     */
    public function getLastMonths(string $accountUuid, int $months = 12): Collection
    {
        $accountUuid = (string) $accountUuid;

        return Cache::remember(
            $this->getCacheKey($accountUuid, "last_{$months}_months"),
            self::CACHE_TTL,
            fn () => Turnover::where('account_uuid', $accountUuid)
                ->orderBy('created_at', 'desc')
                ->take($months)
                ->get()
        );
    }

    /**
     * Get turnover statistics.
     */
    public function getStatistics(string $accountUuid): array
    {
        $accountUuid = (string) $accountUuid;

        return Cache::remember(
            $this->getCacheKey($accountUuid, 'statistics'),
            self::CACHE_TTL,
            function () use ($accountUuid) {
                $turnovers = $this->getLastMonths($accountUuid, 12);

                return [
                    'total_debit'            => $turnovers->sum('debit'),
                    'total_credit'           => $turnovers->sum('credit'),
                    'average_monthly_debit'  => $turnovers->avg('debit') ?? 0,
                    'average_monthly_credit' => $turnovers->avg('credit') ?? 0,
                    'months_analyzed'        => $turnovers->count(),
                ];
            }
        );
    }

    /**
     * Invalidate turnover cache for account.
     */
    public function forget(string $accountUuid): void
    {
        $accountUuid = (string) $accountUuid;
        // Clear all turnover-related cache for this account
        Cache::forget($this->getCacheKey($accountUuid, 'latest'));
        Cache::forget($this->getCacheKey($accountUuid, 'last_12_months'));
        Cache::forget($this->getCacheKey($accountUuid, 'statistics'));
    }

    /**
     * Update cache when new turnover is created.
     */
    public function put(Turnover $turnover): void
    {
        // Invalidate cache to force refresh on next request
        $this->forget((string) $turnover->account_uuid);
    }

    /**
     * Generate cache key for turnover data.
     */
    private function getCacheKey(string $accountUuid, string $type): string
    {
        return self::CACHE_PREFIX . $accountUuid . ':' . $type;
    }
}
