<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\RegTech;

use App\Domain\RegTech\Services\RegTechOrchestrationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class RunHealthChecksMutation
{
    public function __construct(
        private readonly RegTechOrchestrationService $regTechService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $this->regTechService->runHealthChecks();

        return true;
    }
}
