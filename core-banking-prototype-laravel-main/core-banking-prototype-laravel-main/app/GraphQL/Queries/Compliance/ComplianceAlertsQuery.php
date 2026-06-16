<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Compliance;

use App\Domain\Compliance\Models\ComplianceAlert;
use Illuminate\Database\Eloquent\Builder;

class ComplianceAlertsQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return ComplianceAlert::query()->orderBy('created_at', 'desc');
    }
}
