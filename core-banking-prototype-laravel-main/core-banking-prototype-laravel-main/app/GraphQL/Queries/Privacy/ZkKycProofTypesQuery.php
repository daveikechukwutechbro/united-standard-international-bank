<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Privacy;

final class ZkKycProofTypesQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<string>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return [
            'age_proof',
            'residency_proof',
            'kyc_tier_proof',
            'sanctions_clearance_proof',
            'accredited_investor_proof',
        ];
    }
}
