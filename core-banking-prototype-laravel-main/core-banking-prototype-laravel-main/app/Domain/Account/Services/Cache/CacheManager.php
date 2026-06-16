<?php

declare(strict_types=1);

namespace App\Domain\Account\Services\Cache;

use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\Transaction;
use App\Domain\Account\Models\Turnover;

class CacheManager
{
    public function __construct(
        private readonly AccountCacheService $accountCache,
        private readonly TransactionCacheService $transactionCache,
        private readonly TurnoverCacheService $turnoverCache
    ) {
    }

    /**
     * Handle account update event.
     */
    public function onAccountUpdated(Account $account): void
    {
        // Update account in cache
        $this->accountCache->put($account);

        // Update balance cache specifically
        $this->accountCache->updateBalance($account->uuid, $account->balance);
    }

    /**
     * Handle account deletion event.
     */
    public function onAccountDeleted(string $accountUuid): void
    {
        // Clear all caches related to this account
        $this->accountCache->forget($accountUuid);
        $this->transactionCache->forget($accountUuid);
        $this->turnoverCache->forget($accountUuid);
    }

    /**
     * Handle new transaction event.
     */
    public function onTransactionCreated(Transaction $transaction): void
    {
        // Update transaction cache
        $this->transactionCache->put($transaction);

        // Invalidate account balance cache
        $this->accountCache->forget($transaction->account_uuid);
    }

    /**
     * Handle new turnover event.
     */
    public function onTurnoverCreated(Turnover $turnover): void
    {
        // Update turnover cache
        $this->turnoverCache->put($turnover);
    }

    /**
     * Clear all caches (useful for testing or maintenance).
     */
    public function flushAll(): void
    {
        $this->accountCache->flush();
        // Note: In production, implement proper cache tagging
        // to avoid clearing unrelated cache entries
    }

    /**
     * Warm up cache for an account.
     */
    public function warmUp(string $accountUuid): void
    {
        // Pre-load frequently accessed data
        $account = $this->accountCache->get($accountUuid);

        if ($account) {
            $this->accountCache->getBalance($accountUuid);
            $this->transactionCache->getRecent($accountUuid);
            $this->turnoverCache->getLatest($accountUuid);
            $this->turnoverCache->getStatistics($accountUuid);
        }
    }
}
