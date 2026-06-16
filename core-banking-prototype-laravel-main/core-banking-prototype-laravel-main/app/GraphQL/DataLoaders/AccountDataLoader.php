<?php

declare(strict_types=1);

namespace App\GraphQL\DataLoaders;

use App\Domain\Account\Models\Account;
use Illuminate\Support\Collection;

class AccountDataLoader
{
    /**
     * Batch-load accounts by IDs to prevent N+1 queries.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Account>
     */
    public function resolve(array $ids): Collection
    {
        return Account::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Batch-load accounts by UUIDs.
     *
     * @param  array<int, string>  $uuids
     * @return Collection<int, Account>
     */
    public function resolveByUuid(array $uuids): Collection
    {
        return Account::whereIn('uuid', $uuids)->get()->keyBy('uuid');
    }
}
