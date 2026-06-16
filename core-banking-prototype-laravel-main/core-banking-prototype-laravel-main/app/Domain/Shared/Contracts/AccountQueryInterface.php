<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Interface for read-only account queries used by external domains.
 *
 * This interface provides a focused, read-only view of account data
 * for domains that need to query account information without
 * modifying it. Separating reads from writes enables:
 * - Better caching strategies
 * - Projection-based queries
 * - Reduced coupling for read-heavy operations
 *
 * For write operations (credit, debit, transfer), use AccountOperationsInterface.
 *
 * @see \App\Domain\Account\Services\AccountQueryService for implementation
 * @see AccountOperationsInterface for write operations
 */
interface AccountQueryInterface
{
    /**
     * Get account details by UUID.
     *
     * @param string $accountId Account UUID
     * @return array{
     *     id: string,
     *     uuid: string,
     *     user_id: string,
     *     type: string,
     *     status: string,
     *     name: string|null,
     *     metadata: array<string, mixed>,
     *     created_at: string,
     *     updated_at: string
     * }|null Account details or null if not found
     */
    public function getAccountDetails(string $accountId): ?array;

    /**
     * Get the balance for a specific asset in an account.
     *
     * @param string $accountId Account UUID
     * @param string $assetCode Currency/asset code (e.g., 'USD', 'GCU', 'BTC')
     * @return string Balance as string for precision (use bcmath for calculations)
     */
    public function getBalance(string $accountId, string $assetCode): string;

    /**
     * Get all balances for an account.
     *
     * @param string $accountId Account UUID
     * @return array<string, array{
     *     asset_code: string,
     *     available: string,
     *     locked: string,
     *     total: string
     * }> Map of asset code => balance details
     */
    public function getAllBalances(string $accountId): array;

    /**
     * Get the available (unlocked) balance for an asset.
     *
     * @param string $accountId Account UUID
     * @param string $assetCode Currency/asset code
     * @return string Available balance as string
     */
    public function getAvailableBalance(string $accountId, string $assetCode): string;

    /**
     * Get the locked balance for an asset.
     *
     * @param string $accountId Account UUID
     * @param string $assetCode Currency/asset code
     * @return string Locked balance as string
     */
    public function getLockedBalance(string $accountId, string $assetCode): string;

    /**
     * Check if account has sufficient available balance for an operation.
     *
     * @param string $accountId Account UUID
     * @param string $assetCode Currency/asset code
     * @param string $amount Amount to check (as string for precision)
     * @return bool True if available balance >= amount
     */
    public function hasSufficientBalance(string $accountId, string $assetCode, string $amount): bool;

    /**
     * Check if an account exists.
     *
     * @param string $accountId Account UUID
     * @return bool True if account exists
     */
    public function accountExists(string $accountId): bool;

    /**
     * Check if an account is active.
     *
     * @param string $accountId Account UUID
     * @return bool True if account exists and is active
     */
    public function isAccountActive(string $accountId): bool;

    /**
     * Get accounts by owner (user).
     *
     * @param string $ownerId User UUID
     * @param string|null $type Filter by account type (null for all)
     * @param string|null $status Filter by status (null for all)
     * @return array<int, array{
     *     id: string,
     *     uuid: string,
     *     type: string,
     *     status: string,
     *     name: string|null,
     *     created_at: string
     * }>
     */
    public function getAccountsByOwner(
        string $ownerId,
        ?string $type = null,
        ?string $status = null
    ): array;

    /**
     * Get transaction history for an account.
     *
     * @param string $accountId Account UUID
     * @param array<string, mixed> $filters Optional filters:
     *     - asset_code: string - Filter by asset
     *     - type: string - Filter by transaction type
     *     - date_from: string - Start date (ISO 8601)
     *     - date_to: string - End date (ISO 8601)
     * @param int $limit Maximum number of results
     * @param int $offset Pagination offset
     * @return array{
     *     transactions: array<int, array{
     *         id: string,
     *         type: string,
     *         asset_code: string,
     *         amount: string,
     *         balance_after: string,
     *         reference: string,
     *         created_at: string
     *     }>,
     *     total: int,
     *     has_more: bool
     * }
     */
    public function getTransactionHistory(
        string $accountId,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Get a specific transaction by ID.
     *
     * @param string $transactionId Transaction UUID
     * @return array{
     *     id: string,
     *     account_id: string,
     *     type: string,
     *     asset_code: string,
     *     amount: string,
     *     balance_before: string,
     *     balance_after: string,
     *     reference: string,
     *     metadata: array<string, mixed>,
     *     created_at: string
     * }|null Transaction details or null if not found
     */
    public function getTransaction(string $transactionId): ?array;

    /**
     * Get active balance locks for an account.
     *
     * @param string $accountId Account UUID
     * @param string|null $assetCode Filter by asset code (null for all)
     * @return array<int, array{
     *     lock_id: string,
     *     asset_code: string,
     *     amount: string,
     *     reason: string,
     *     created_at: string,
     *     expires_at: string|null
     * }>
     */
    public function getActiveLocks(string $accountId, ?string $assetCode = null): array;

    /**
     * Get account summary with balances across all assets.
     *
     * @param string $accountId Account UUID
     * @return array{
     *     account: array{
     *         id: string,
     *         type: string,
     *         status: string,
     *         name: string|null
     *     },
     *     balances: array<string, array{
     *         available: string,
     *         locked: string,
     *         total: string
     *     }>,
     *     total_value_usd: string|null,
     *     last_activity_at: string|null
     * }|null Account summary or null if not found
     */
    public function getAccountSummary(string $accountId): ?array;

    /**
     * Search accounts by criteria.
     *
     * @param array<string, mixed> $criteria Search criteria:
     *     - owner_id: string - Owner UUID
     *     - type: string - Account type
     *     - status: string - Account status
     *     - has_balance_in: string - Has balance in this asset
     *     - min_balance: string - Minimum total balance
     * @param int $limit Maximum number of results
     * @param int $offset Pagination offset
     * @return array{
     *     accounts: array<int, array{
     *         id: string,
     *         uuid: string,
     *         owner_id: string,
     *         type: string,
     *         status: string
     *     }>,
     *     total: int,
     *     has_more: bool
     * }
     */
    public function searchAccounts(
        array $criteria,
        int $limit = 50,
        int $offset = 0
    ): array;
}
