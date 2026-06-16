<?php

declare(strict_types=1);

namespace App\Domain\Account\Services;

use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\AccountBalance;
use App\Domain\Account\Models\Transaction;
use App\Domain\Shared\Contracts\AccountQueryInterface;
use App\Domain\Shared\Validation\FinancialInputValidator;

class AccountQueryService implements AccountQueryInterface
{
    use FinancialInputValidator;

    /**
     * {@inheritDoc}
     */
    public function getAccountDetails(string $accountId): ?array
    {
        $this->validateUuid($accountId, 'account ID');

        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            return null;
        }

        return [
            'id'         => (string) $account->id,
            'uuid'       => $account->uuid,
            'user_id'    => (string) $account->user_id,
            'type'       => $account->type ?? 'standard',
            'status'     => $account->status ?? 'active',
            'name'       => $account->name ?? null,
            'metadata'   => $account->metadata ?? [],
            'created_at' => $account->created_at?->toIso8601String() ?? '',
            'updated_at' => $account->updated_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(string $accountId, string $assetCode): string
    {
        $this->validateUuid($accountId, 'account ID');
        $this->validateAssetCode($assetCode);

        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            return '0';
        }

        return (string) $account->getBalance($assetCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllBalances(string $accountId): array
    {
        $this->validateUuid($accountId, 'account ID');

        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            return [];
        }

        $balances = [];

        /** @var AccountBalance $balance */
        foreach ($account->balances ?? [] as $balance) {
            $available = (string) ($balance->amount ?? 0);
            $locked = (string) ($balance->locked_amount ?? 0);

            /** @var numeric-string $a */
            $a = $available;
            /** @var numeric-string $b */
            $b = $locked;

            $balances[$balance->asset_code] = [
                'asset_code' => $balance->asset_code,
                'available'  => $available,
                'locked'     => $locked,
                'total'      => bcadd($a, $b, 8),
            ];
        }

        return $balances;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableBalance(string $accountId, string $assetCode): string
    {
        return $this->getBalance($accountId, $assetCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getLockedBalance(string $accountId, string $assetCode): string
    {
        $this->validateUuid($accountId, 'account ID');
        $this->validateAssetCode($assetCode);

        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            return '0';
        }

        $balance = $account->balances()
            ->where('asset_code', $assetCode)
            ->first();

        return (string) ($balance->locked_amount ?? 0);
    }

    /**
     * {@inheritDoc}
     */
    public function hasSufficientBalance(string $accountId, string $assetCode, string $amount): bool
    {
        $this->validateUuid($accountId, 'account ID');
        $this->validateAssetCode($assetCode);
        $this->validateNonNegativeAmount($amount);

        $balance = $this->getBalance($accountId, $assetCode);

        /** @var numeric-string $a */
        $a = $balance;
        /** @var numeric-string $b */
        $b = $amount;

        return bccomp($a, $b, 8) >= 0;
    }

    /**
     * {@inheritDoc}
     */
    public function accountExists(string $accountId): bool
    {
        $this->validateUuid($accountId, 'account ID');

        return Account::where('uuid', $accountId)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountActive(string $accountId): bool
    {
        $this->validateUuid($accountId, 'account ID');

        return Account::where('uuid', $accountId)
            ->where('status', 'active') // @phpstan-ignore argument.type (valid Eloquent column name)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountsByOwner(
        string $ownerId,
        ?string $type = null,
        ?string $status = null
    ): array {
        $query = Account::where('user_id', $ownerId);

        if ($type !== null) {
            $query->where('type', $type); // @phpstan-ignore argument.type (valid Eloquent column name)
        }

        if ($status !== null) {
            $query->where('status', $status); // @phpstan-ignore argument.type (valid Eloquent column name)
        }

        return $query->get()->map(fn ($account) => [
            'id'         => (string) $account->id,
            'uuid'       => $account->uuid,
            'type'       => $account->type ?? 'standard',
            'status'     => $account->status ?? 'active',
            'name'       => $account->name ?? null,
            'created_at' => $account->created_at?->toIso8601String() ?? '',
        ])->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionHistory(
        string $accountId,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array {
        $this->validateUuid($accountId, 'account ID');

        // Validate and sanitize pagination
        $limit = max(1, min(100, $limit)); // Limit between 1-100
        $offset = max(0, $offset);

        $query = Transaction::where('account_uuid', $accountId);

        if (isset($filters['asset_code'])) {
            $this->validateAssetCode($filters['asset_code']);
            $query->where('asset_code', $filters['asset_code']); // @phpstan-ignore argument.type
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']); // @phpstan-ignore argument.type
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();

        // @phpstan-ignore method.unresolvableReturnType (Eloquent Collection generics issue)
        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            // @phpstan-ignore argument.unresolvableType
            ->map(fn ($tx) => [
                'id'            => (string) $tx->id,
                'type'          => $tx->type ?? 'unknown',
                'asset_code'    => $tx->asset_code ?? '',
                'amount'        => (string) ($tx->amount ?? 0),
                'balance_after' => (string) ($tx->balance_after ?? 0),
                'reference'     => $tx->reference ?? '',
                // @phpstan-ignore method.nonObject, nullsafe.neverNull (created_at is Carbon)
                'created_at' => $tx->created_at?->toIso8601String() ?? '',
            ])
            ->toArray();

        return [
            'transactions' => $transactions,
            'total'        => $total,
            'has_more'     => ($offset + $limit) < $total,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransaction(string $transactionId): ?array
    {
        $tx = Transaction::find($transactionId);

        if (! $tx) {
            return null;
        }

        // @phpstan-ignore return.type (SchemalessAttributes metadata is array-like)
        return [
            'id'             => (string) $tx->id,
            'account_id'     => (string) ($tx->account_uuid ?? ''),
            'type'           => (string) ($tx->type ?? 'unknown'),
            'asset_code'     => (string) ($tx->asset_code ?? ''),
            'amount'         => (string) ($tx->amount ?? 0),
            'balance_before' => (string) ($tx->balance_before ?? 0),
            'balance_after'  => (string) ($tx->balance_after ?? 0),
            'reference'      => (string) ($tx->reference ?? ''),
            'metadata'       => $tx->metadata->toArray(),
            // @phpstan-ignore nullsafe.neverNull (created_at can be null for unsaved models)
            'created_at' => $tx->created_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveLocks(string $accountId, ?string $assetCode = null): array
    {
        $this->validateUuid($accountId, 'account ID');

        if ($assetCode !== null) {
            $this->validateAssetCode($assetCode);
        }

        // Note: In this implementation, locks are stored in cache
        // A production implementation would query a database table
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountSummary(string $accountId): ?array
    {
        $this->validateUuid($accountId, 'account ID');

        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            return null;
        }

        $balances = $this->getAllBalances($accountId);

        return [
            'account' => [
                'id'     => $account->uuid,
                'type'   => $account->type ?? 'standard',
                'status' => $account->status ?? 'active',
                'name'   => $account->name ?? null,
            ],
            'balances' => array_map(fn ($b) => [
                'available' => $b['available'],
                'locked'    => $b['locked'],
                'total'     => $b['total'],
            ], $balances),
            'total_value_usd'  => null, // Would require exchange rate calculation
            'last_activity_at' => $account->updated_at?->toIso8601String(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function searchAccounts(
        array $criteria,
        int $limit = 50,
        int $offset = 0
    ): array {
        // Validate and sanitize pagination
        $limit = max(1, min(100, $limit)); // Limit between 1-100
        $offset = max(0, $offset);

        $query = Account::query();

        if (isset($criteria['owner_id'])) {
            $query->where('user_id', $criteria['owner_id']);
        }

        if (isset($criteria['type'])) {
            // @phpstan-ignore argument.type (type column exists in database)
            $query->where('type', $criteria['type']);
        }

        if (isset($criteria['status'])) {
            // @phpstan-ignore argument.type (status column exists in database)
            $query->where('status', $criteria['status']);
        }

        $total = $query->count();

        $accounts = $query
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(fn ($account) => [
                'id'       => (string) $account->id,
                'uuid'     => $account->uuid,
                'owner_id' => (string) $account->user_id,
                'type'     => $account->type ?? 'standard',
                'status'   => $account->status ?? 'active',
            ])
            ->toArray();

        return [
            'accounts' => $accounts,
            'total'    => $total,
            'has_more' => ($offset + $limit) < $total,
        ];
    }
}
