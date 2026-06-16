<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Cgo;

use App\Domain\Cgo\Models\CgoInvestment;

class InvestmentQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): CgoInvestment
    {
        /** @var CgoInvestment */
        return CgoInvestment::findOrFail($args['id']);
    }
}
