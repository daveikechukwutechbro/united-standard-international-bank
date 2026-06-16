<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Treasury;

use App\Domain\Treasury\Models\AssetAllocation;
use Illuminate\Database\Eloquent\Builder;

class PortfolioQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return AssetAllocation::query()->orderBy('created_at', 'desc');
    }
}
