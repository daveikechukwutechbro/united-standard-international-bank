<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\FinancialInstitution;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;

class PartnerQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): FinancialInstitutionPartner
    {
        /** @var FinancialInstitutionPartner */
        return FinancialInstitutionPartner::findOrFail($args['id']);
    }
}
