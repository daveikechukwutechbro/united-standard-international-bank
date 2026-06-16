<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\RegTech;

use App\Domain\RegTech\Services\RegTechOrchestrationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class ComplianceSummaryQuery
{
    public function __construct(
        private readonly RegTechOrchestrationService $regTechService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $summary = $this->regTechService->getComplianceSummary($args['jurisdiction']);

        return [
            'jurisdiction' => $args['jurisdiction'],
            'regulations'  => $summary['regulations'] ?? [],
            'status'       => $summary['status'] ?? 'unknown',
            'last_check'   => $summary['last_check'] ?? null,
        ];
    }
}
