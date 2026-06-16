<?php

declare(strict_types=1);

namespace App\GraphQL\DataLoaders;

use App\Domain\Wallet\Models\MultiSigWallet;
use Illuminate\Support\Collection;

class WalletDataLoader
{
    /**
     * Batch-load wallets by IDs to prevent N+1 queries.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, MultiSigWallet>
     */
    public function resolve(array $ids): Collection
    {
        return MultiSigWallet::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Batch-load wallets by user ID.
     *
     * @param  array<int, int>  $userIds
     * @return Collection<int, Collection<int, MultiSigWallet>>
     */
    public function resolveByUserId(array $userIds): Collection
    {
        return MultiSigWallet::whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');
    }
}
