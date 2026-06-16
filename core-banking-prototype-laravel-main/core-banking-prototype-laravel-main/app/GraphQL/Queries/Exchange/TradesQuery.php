<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Exchange;

use App\Domain\Exchange\Projections\Trade;
use Illuminate\Database\Eloquent\Builder;

class TradesQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return Trade::query()->orderBy('created_at', 'desc');
    }
}
