<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Compliance;

use App\Domain\Compliance\Models\ComplianceCase;
use Illuminate\Database\Eloquent\Builder;

class ComplianceCasesQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return ComplianceCase::query()->orderBy('created_at', 'desc');
    }
}
