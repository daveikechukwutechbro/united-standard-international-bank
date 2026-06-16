<?php

declare(strict_types=1);

namespace App\Domain\Account\Services\Cache;

use App\Domain\Account\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TransactionCacheService
{
    /**
     * Cache key prefix for transactions.
     */
    private const CACHE_PREFIX = 'transaction:';

    /**
     * Cache duration in seconds (30 minutes for transactions).
     */
    private const CACHE_TTL = 1800;

    /**
     * Get recent transactions for account.
     */
    public function getRecent(string $accountUuid, int $limit = 10): Collection
    {
        return Cache::remember(
            $this->getCacheKey($accountUuid, "recent_{$limit}"),
            self::CACHE_TTL,
            fn () => Transaction::where('aggregate_uuid', $accountUuid)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get()
        );
    }

    /**
     * Get paginated transactions (cached per page).
     */
    public function getPaginated(string $accountUuid, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey($accountUuid, "page_{$page}_per_{$perPage}");

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($accountUuid, $perPage, $page) {
                return Transaction::where('aggregate_uuid', $accountUuid)
                    ->orderBy('created_at', 'desc')
                    ->paginate(perPage: $perPage, page: $page);
            }
        );
    }

    /**
     * Get transaction by UUID.
     */
    public function get(string $uuid): ?Transaction
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'uuid:' . $uuid,
            self::CACHE_TTL,
            fn () => Transaction::where('id', $uuid)->first()
        );
    }

    /**
     * Get daily transaction summary.
     */
    public function getDailySummary(string $accountUuid, string $date): array
    {
        return Cache::remember(
            $this->getCacheKey($accountUuid, "daily_summary_{$date}"),
            86400, // 24 hours for daily summaries
            function () use ($accountUuid, $date) {
                $transactions = Transaction::where('aggregate_uuid', $accountUuid)
                    ->whereDate('created_at', $date)
                    ->get();

                $totalDeposits = 0;
                $totalWithdrawals = 0;

                foreach ($transactions as $t) {
                    $type = $t->event_properties['type'] ?? '';
                    $amount = $t->event_properties['amount'] ?? 0;

                    if ($type === 'deposit') {
                        $totalDeposits += $amount;
                    } elseif ($type === 'withdrawal') {
                        $totalWithdrawals += $amount;
                    }
                }

                return [
                    'date'              => $date,
                    'total_deposits'    => $totalDeposits,
                    'total_withdrawals' => $totalWithdrawals,
                    'transaction_count' => $transactions->count(),
                    'net_change'        => $totalDeposits - $totalWithdrawals,
                ];
            }
        );
    }

    /**
     * Invalidate transaction cache for account.
     */
    public function forget(string $accountUuid): void
    {
        // Clear all transaction-related cache for this account
        // In production, consider using cache tags for more efficient clearing
        $patterns = [
            'recent_*',
            'page_*',
            'daily_summary_*',
        ];

        foreach ($patterns as $pattern) {
            // This is a simplified approach. In production, use Redis SCAN
            // or cache tags for better performance
            Cache::forget($this->getCacheKey($accountUuid, $pattern));
        }
    }

    /**
     * Update cache when new transaction is created.
     */
    public function put(Transaction $transaction): void
    {
        // Cache the individual transaction
        Cache::put(
            self::CACHE_PREFIX . 'uuid:' . $transaction->id,
            $transaction,
            self::CACHE_TTL
        );

        // Invalidate account-related caches to force refresh
        $this->forget($transaction->aggregate_uuid);
    }

    /**
     * Generate cache key for transaction data.
     */
    private function getCacheKey(string $accountUuid, string $type): string
    {
        return self::CACHE_PREFIX . 'account:' . $accountUuid . ':' . $type;
    }
}
