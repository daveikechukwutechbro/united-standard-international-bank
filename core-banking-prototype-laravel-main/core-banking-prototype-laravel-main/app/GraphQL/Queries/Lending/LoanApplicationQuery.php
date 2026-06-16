<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Lending;

use App\Domain\Lending\Models\LoanApplication;
use Illuminate\Database\Eloquent\Builder;

class LoanApplicationQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return LoanApplication::query()->orderBy('created_at', 'desc');
    }
}
