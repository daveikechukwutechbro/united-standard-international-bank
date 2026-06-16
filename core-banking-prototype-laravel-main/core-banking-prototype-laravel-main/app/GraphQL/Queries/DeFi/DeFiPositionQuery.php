<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\DeFi;

use App\Domain\DeFi\Models\DeFiPosition;
use Illuminate\Database\Eloquent\Builder;

class DeFiPositionQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return DeFiPosition::query()->orderBy('created_at', 'desc');
    }
}
