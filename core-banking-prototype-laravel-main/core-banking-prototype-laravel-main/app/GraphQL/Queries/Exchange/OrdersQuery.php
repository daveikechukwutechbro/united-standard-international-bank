<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Exchange;

use App\Domain\Exchange\Projections\Order;
use Illuminate\Database\Eloquent\Builder;

class OrdersQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return Order::query()->orderBy('created_at', 'desc');
    }
}
