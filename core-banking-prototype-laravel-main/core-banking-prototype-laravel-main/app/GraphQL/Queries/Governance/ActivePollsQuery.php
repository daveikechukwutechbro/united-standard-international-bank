<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Governance;

use App\Domain\Governance\Services\GovernanceService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class ActivePollsQuery
{
    public function __construct(
        private readonly GovernanceService $governanceService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Domain\Governance\Models\Poll>
     */
    public function __invoke(mixed $rootValue, array $args): mixed
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->governanceService->getActivePolls();
    }
}
