<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Fraud;

use App\Domain\Fraud\Models\FraudCase;
use Illuminate\Database\Eloquent\Builder;

class FraudCaseQuery
{
    /**
     * @return Builder<FraudCase>
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return FraudCase::query()->orderBy('created_at', 'desc');
    }
}
