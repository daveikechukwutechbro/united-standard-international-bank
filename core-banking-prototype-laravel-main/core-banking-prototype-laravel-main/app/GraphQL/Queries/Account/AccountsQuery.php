<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Account;

use App\Domain\Account\Models\Account;
use Illuminate\Database\Eloquent\Builder;

class AccountsQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return Account::query()->orderBy('created_at', 'desc');
    }
}
