<?php

declare(strict_types=1);

namespace App\Domain\Account\Services\Cache;

use App\Domain\Account\Models\Account;
use Illuminate\Support\Facades\Cache;

class AccountCacheService
{
    /**
     * Cache key prefix for accounts.
     */
    private const CACHE_PREFIX = 'account:';

    /**
     * Cache duration in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get account from cache or database.
     */
    public function get(string $uuid): ?Account
    {
        $uuid = (string) $uuid;

        return Cache::remember(
            $this->getCacheKey($uuid),
            self::CACHE_TTL,
            fn () => Account::where('uuid', $uuid)->first()
        );
    }

    /**
     * Update account in cache.
     */
    public function put(Account $account): void
    {
        $uuid = (string) $account->uuid;
        Cache::put(
            $this->getCacheKey($uuid),
            $account,
            self::CACHE_TTL
        );
    }

    /**
     * Remove account from cache.
     */
    public function forget(string $uuid): void
    {
        $uuid = (string) $uuid;
        Cache::forget($this->getCacheKey($uuid));
        Cache::forget($this->getCacheKey($uuid) . ':balance');
    }

    /**
     * Clear all account cache entries.
     */
    public function flush(): void
    {
        // In production, you might want to use tags for better cache management
        // For now, we'll clear individual keys
        Cache::flush();
    }

    /**
     * Get balance from cache with shorter TTL for more frequent updates.
     */
    public function getBalance(string $uuid): ?int
    {
        /** @var Account|null $account */
        $account = null;
        /** @var Account|null $account */
        $account = null;
        $uuid = (string) $uuid;
        $key = $this->getCacheKey($uuid) . ':balance';

        $balance = Cache::remember(
            $key,
            300, // 5 minutes for balance
            function () use ($uuid) {
                /** @var \Illuminate\Database\Eloquent\Model|null $account */
                $account = Account::where('uuid', $uuid)->first();
                if (! $account) {
                    return null;
                }

                // For backward compatibility, return USD balance
                $usdBalance = $account->balances()
                    ->where('asset_code', 'USD')
                    ->first();

                return $usdBalance ? $usdBalance->balance : 0;
            }
        );

        return $balance === null ? null : (int) $balance;
    }

    /**
     * Update balance in cache.
     */
    public function updateBalance(string $uuid, int $balance): void
    {
        $uuid = (string) $uuid;
        $key = $this->getCacheKey($uuid) . ':balance';
        Cache::put($key, $balance, 300);

        // Only invalidate the main account cache, not the balance
        Cache::forget($this->getCacheKey($uuid));
    }

    /**
     * Generate cache key for account.
     */
    private function getCacheKey(string $uuid): string
    {
        return self::CACHE_PREFIX . $uuid;
    }
}
