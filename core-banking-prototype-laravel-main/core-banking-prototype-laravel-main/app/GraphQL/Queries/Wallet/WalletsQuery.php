<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Wallet;

use App\Domain\Wallet\Models\MultiSigWallet;
use Illuminate\Database\Eloquent\Builder;

class WalletsQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return MultiSigWallet::query()->orderBy('created_at', 'desc');
    }
}
