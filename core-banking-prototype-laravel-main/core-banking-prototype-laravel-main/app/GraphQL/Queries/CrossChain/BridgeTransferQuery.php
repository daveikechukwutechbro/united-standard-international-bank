<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CrossChain;

use App\Domain\CrossChain\Models\BridgeTransaction;
use Illuminate\Database\Eloquent\Builder;

class BridgeTransferQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return BridgeTransaction::query()->orderBy('created_at', 'desc');
    }
}
