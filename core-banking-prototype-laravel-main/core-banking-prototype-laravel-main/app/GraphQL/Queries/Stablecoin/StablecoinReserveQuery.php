<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Stablecoin;

use App\Domain\Stablecoin\Models\StablecoinReserve;
use Illuminate\Database\Eloquent\Builder;

class StablecoinReserveQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return StablecoinReserve::query()->orderBy('created_at', 'desc');
    }
}
